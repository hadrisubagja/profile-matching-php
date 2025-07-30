<?php
require_once __DIR__ . '/../config/database.php';

class ProfileMatching {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function calculateBatch($batchId) {
        try {
            $this->db->beginTransaction();
            
            // Get participants in the batch
            $participants = $this->getParticipants($batchId);
            if (empty($participants)) {
                throw new Exception("No participants found in this batch");
            }
            
            // Get criteria
            $criteria = $this->getCriteria();
            if (empty($criteria)) {
                throw new Exception("No criteria found");
            }
            
            // Get GAP weights
            $gapWeights = $this->getGapWeights();
            
            $results = [];
            
            foreach ($participants as $participant) {
                $result = $this->calculateParticipant($participant, $criteria, $gapWeights);
                $results[] = $result;
            }
            
            // Sort by final score (descending)
            usort($results, function($a, $b) {
                return $b['final_score'] <=> $a['final_score'];
            });
            
            // Assign rankings and determine acceptance (top 10 or based on threshold)
            $acceptedCount = min(10, count($results)); // Accept top 10 or all if less than 10
            
            foreach ($results as $index => $result) {
                $ranking = $index + 1;
                $status = $ranking <= $acceptedCount ? 'accepted' : 'rejected';
                
                // Save calculation result
                $this->saveCalculationResult($result['participant_id'], $result, $ranking, $status);
                
                // Update participant status
                $this->updateParticipantStatus($result['participant_id'], $status === 'accepted' ? 'accepted' : 'rejected');
            }
            
            // Update batch status
            $this->updateBatchStatus($batchId, 'calculated');
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Calculation completed successfully',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function getParticipants($batchId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name 
            FROM participants p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.batch_id = ? AND p.status IN ('registered', 'evaluated')
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    
    private function getCriteria() {
        $stmt = $this->db->prepare("SELECT * FROM criteria WHERE is_active = 1 ORDER BY factor_type, id");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function getGapWeights() {
        $stmt = $this->db->prepare("SELECT gap_value, weight_value FROM gap_weights");
        $stmt->execute();
        $weights = [];
        while ($row = $stmt->fetch()) {
            $weights[$row['gap_value']] = $row['weight_value'];
        }
        return $weights;
    }
    
    private function calculateParticipant($participant, $criteria, $gapWeights) {
        $scores = $this->getParticipantScores($participant['id']);
        
        $coreFactors = [];
        $secondaryFactors = [];
        
        foreach ($criteria as $criterion) {
            $score = $scores[$criterion['id']] ?? 0;
            $gap = $score - $criterion['target_value'];
            $weight = $gapWeights[$gap] ?? 1.0; // Default weight if gap not found
            
            // Save individual score calculation
            $this->saveParticipantScore($participant['id'], $criterion['id'], $score, $gap, $weight);
            
            if ($criterion['factor_type'] === 'core') {
                $coreFactors[] = $weight;
            } else {
                $secondaryFactors[] = $weight;
            }
        }
        
        // Calculate averages
        $coreAvg = !empty($coreFactors) ? array_sum($coreFactors) / count($coreFactors) : 0;
        $secondaryAvg = !empty($secondaryFactors) ? array_sum($secondaryFactors) / count($secondaryFactors) : 0;
        
        // Calculate final score: 60% core factor + 40% secondary factor
        $finalScore = ($coreAvg * 0.6) + ($secondaryAvg * 0.4);
        
        return [
            'participant_id' => $participant['id'],
            'participant_name' => $participant['full_name'],
            'core_factor_avg' => round($coreAvg, 3),
            'secondary_factor_avg' => round($secondaryAvg, 3),
            'final_score' => round($finalScore, 3)
        ];
    }
    
    private function getParticipantScores($participantId) {
        $stmt = $this->db->prepare("SELECT criteria_id, score_value FROM participant_scores WHERE participant_id = ?");
        $stmt->execute([$participantId]);
        $scores = [];
        while ($row = $stmt->fetch()) {
            $scores[$row['criteria_id']] = $row['score_value'];
        }
        return $scores;
    }
    
    private function saveParticipantScore($participantId, $criteriaId, $score, $gap, $weight) {
        $stmt = $this->db->prepare("
            INSERT INTO participant_scores (participant_id, criteria_id, score_value, gap_value, weight_value) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            score_value = VALUES(score_value), 
            gap_value = VALUES(gap_value), 
            weight_value = VALUES(weight_value)
        ");
        $stmt->execute([$participantId, $criteriaId, $score, $gap, $weight]);
    }
    
    private function saveCalculationResult($participantId, $result, $ranking, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO calculation_results (participant_id, core_factor_avg, secondary_factor_avg, final_score, ranking, status) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            core_factor_avg = VALUES(core_factor_avg),
            secondary_factor_avg = VALUES(secondary_factor_avg),
            final_score = VALUES(final_score),
            ranking = VALUES(ranking),
            status = VALUES(status),
            calculated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $participantId,
            $result['core_factor_avg'],
            $result['secondary_factor_avg'],
            $result['final_score'],
            $ranking,
            $status
        ]);
    }
    
    private function updateParticipantStatus($participantId, $status) {
        $stmt = $this->db->prepare("UPDATE participants SET status = ? WHERE id = ?");
        $stmt->execute([$status, $participantId]);
    }
    
    private function updateBatchStatus($batchId, $status) {
        $stmt = $this->db->prepare("UPDATE batches SET status = ? WHERE id = ?");
        $stmt->execute([$status, $batchId]);
    }
    
    public function getBatchResults($batchId) {
        $stmt = $this->db->prepare("
            SELECT 
                cr.*,
                u.full_name,
                p.registration_number,
                b.name as batch_name
            FROM calculation_results cr
            JOIN participants p ON cr.participant_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN batches b ON p.batch_id = b.id
            WHERE p.batch_id = ?
            ORDER BY cr.ranking ASC
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    
    public function getParticipantDetail($participantId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                u.full_name,
                u.email,
                b.name as batch_name,
                cr.core_factor_avg,
                cr.secondary_factor_avg,
                cr.final_score,
                cr.ranking,
                cr.status as result_status
            FROM participants p
            JOIN users u ON p.user_id = u.id
            JOIN batches b ON p.batch_id = b.id
            LEFT JOIN calculation_results cr ON cr.participant_id = p.id
            WHERE p.id = ?
        ");
        $stmt->execute([$participantId]);
        $participant = $stmt->fetch();
        
        if ($participant) {
            // Get detailed scores
            $stmt = $this->db->prepare("
                SELECT 
                    ps.*,
                    c.name as criteria_name,
                    c.target_value,
                    c.factor_type
                FROM participant_scores ps
                JOIN criteria c ON ps.criteria_id = c.id
                WHERE ps.participant_id = ?
                ORDER BY c.factor_type, c.name
            ");
            $stmt->execute([$participantId]);
            $participant['scores'] = $stmt->fetchAll();
        }
        
        return $participant;
    }
}
?>