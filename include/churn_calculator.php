<?php
// /include/churn_calculator.php
class ChurnCalculator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate churn probability for all customers
     */
    public function calculateChurnForAllCustomers() {
        $query = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                u.membership_type,
                u.points,
                u.login_streak,
                u.daily_spins,
                u.last_login_date,
                u.last_spin_date,
                u.created_at,
                u.gender,
                
                -- Order metrics
                COALESCE(o.last_order_date, u.created_at) as last_order_date,
                COALESCE(o.total_orders, 0) as total_orders,
                COALESCE(o.total_spent, 0) as total_spent,
                
                -- Abandoned carts
                COALESCE(c.abandoned_carts_count, 0) as abandoned_carts_count,
                
                -- Calculate churn score (0-100) - ALIGNED with analytics.php
                ROUND((
                    -- Login recency (30% weight)
                    (CASE 
                        WHEN u.last_login_date IS NULL THEN 100
                        WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 90
                        WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 70
                        WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 50
                        WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 30
                        ELSE 10 
                    END * 0.30) +
                    
                    -- Order recency (35% weight)
                    (CASE 
                        WHEN o.last_order_date IS NULL THEN 80
                        WHEN DATEDIFF(CURDATE(), o.last_order_date) > 90 THEN 90
                        WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 70
                        WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 40
                        ELSE 10 
                    END * 0.35) +
                    
                    -- Abandoned carts (20% weight)
                    (CASE 
                        WHEN COALESCE(c.abandoned_carts_count, 0) > 2 THEN 60
                        WHEN COALESCE(c.abandoned_carts_count, 0) > 0 THEN 30
                        ELSE 10 
                    END * 0.20) +
                    
                    -- Engagement (15% weight)
                    (CASE 
                        WHEN u.last_spin_date IS NULL THEN 70
                        WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 50
                        WHEN u.daily_spins = 0 THEN 30
                        ELSE 10 
                    END * 0.15)
                    
                ), 2) as churn_score,
                
                -- Risk category (ALIGNED with analytics.php)
                CASE 
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 100 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 90 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 70 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 50 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 30 ELSE 10 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 80 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 90 THEN 90 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 70 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 40 ELSE 10 END * 0.35) +
                        (CASE WHEN COALESCE(c.abandoned_carts_count, 0) > 2 THEN 60 WHEN COALESCE(c.abandoned_carts_count, 0) > 0 THEN 30 ELSE 10 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 70 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 50 WHEN u.daily_spins = 0 THEN 30 ELSE 10 END * 0.15)
                    ) > 70 THEN 'high'
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 100 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 90 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 70 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 50 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 30 ELSE 10 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 80 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 90 THEN 90 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 70 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 40 ELSE 10 END * 0.35) +
                        (CASE WHEN COALESCE(c.abandoned_carts_count, 0) > 2 THEN 60 WHEN COALESCE(c.abandoned_carts_count, 0) > 0 THEN 30 ELSE 10 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 70 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 50 WHEN u.daily_spins = 0 THEN 30 ELSE 10 END * 0.15)
                    ) > 40 THEN 'medium'
                    ELSE 'low'
                END as risk_category,
                
                -- Actual churn status (for ML training)
                CASE 
                    WHEN u.last_login_date IS NULL THEN 1
                    WHEN DATEDIFF(CURDATE(), u.last_login_date) >= 90 THEN 1
                    ELSE 0
                END as is_churned_actual

            FROM users u
            LEFT JOIN (
                SELECT 
                    user_id, 
                    MAX(created_at) as last_order_date,
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_spent
                FROM orders 
                WHERE status NOT IN ('canceled') 
                GROUP BY user_id
            ) o ON u.user_id = o.user_id
            LEFT JOIN (
                SELECT 
                    c.user_id, 
                    COUNT(DISTINCT c.cart_id) as abandoned_carts_count
                FROM carts c
                JOIN cart_items ci ON c.cart_id = ci.cart_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM orders o 
                    WHERE o.user_id = c.user_id 
                    AND o.created_at >= c.created_at
                    AND o.status NOT IN ('canceled', 'pending_payment')
                )
                AND c.created_at <= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                GROUP BY c.user_id
            ) c ON u.user_id = c.user_id
            WHERE u.role = 'customer'
            ORDER BY churn_score DESC
        ";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Churn calculation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get churn overview statistics
     */
    public function getChurnOverview() {
        $customers = $this->calculateChurnForAllCustomers();
        
        if (empty($customers)) {
            return [
                'total_customers' => 0,
                'high_risk_customers' => 0,
                'medium_risk_customers' => 0,
                'low_risk_customers' => 0,
                'avg_churn_rate' => 0
            ];
        }
        
        $total = count($customers);
        $high = 0;
        $medium = 0;
        $low = 0;
        $total_score = 0;
        
        foreach ($customers as $customer) {
            switch ($customer['risk_category']) {
                case 'high': $high++; break;
                case 'medium': $medium++; break;
                case 'low': $low++; break;
            }
            $total_score += $customer['churn_score'];
        }
        
        return [
            'total_customers' => $total,
            'high_risk_customers' => $high,
            'medium_risk_customers' => $medium,
            'low_risk_customers' => $low,
            'avg_churn_rate' => $total > 0 ? round($total_score / $total, 1) : 0
        ];
    }
    
    /**
     * Get customers by risk category
     */
    public function getCustomersByRisk($risk_category, $limit = 20) {
        $customers = $this->calculateChurnForAllCustomers();
        
        $filtered = array_filter($customers, function($customer) use ($risk_category) {
            return $customer['risk_category'] === $risk_category;
        });
        
        // Sort by churn score descending
        usort($filtered, function($a, $b) {
            return $b['churn_score'] <=> $a['churn_score'];
        });
        
        return array_slice($filtered, 0, $limit);
    }
    
    /**
     * Get feature importance analysis
     */
    public function getFeatureImportance() {
        $query = "
            SELECT 
                'last_login_days' as feature,
                AVG(CASE 
                    WHEN u.last_login_date IS NULL THEN 100
                    WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 70
                    WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 50
                    ELSE 10 
                END) as importance,
                'Login inactivity is strongest predictor' as insight
            FROM users u WHERE u.role = 'customer'
            UNION ALL
            SELECT 
                'order_recency' as feature,
                AVG(CASE 
                    WHEN o.last_order_date IS NULL THEN 80
                    WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 70
                    WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 40
                    ELSE 10 
                END) as importance,
                'Order recency strongly correlates with retention' as insight
            FROM users u
            LEFT JOIN (
                SELECT user_id, MAX(created_at) as last_order_date 
                FROM orders WHERE status NOT IN ('canceled') GROUP BY user_id
            ) o ON u.user_id = o.user_id
            WHERE u.role = 'customer'
            UNION ALL
            SELECT 
                'abandoned_carts' as feature,
                AVG(CASE 
                    WHEN COALESCE(c.abandoned_carts_count, 0) > 0 THEN 45
                    ELSE 10 
                END) as importance,
                'Cart abandonment shows purchase intent issues' as insight
            FROM users u
            LEFT JOIN (
                SELECT c.user_id, COUNT(DISTINCT c.cart_id) as abandoned_carts_count
                FROM carts c
                JOIN cart_items ci ON c.cart_id = ci.cart_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM orders o 
                    WHERE o.user_id = c.user_id 
                    AND o.created_at >= c.created_at
                    AND o.status NOT IN ('canceled', 'pending_payment')
                )
                GROUP BY c.user_id
            ) c ON u.user_id = c.user_id
            WHERE u.role = 'customer'
            UNION ALL
            SELECT 
                'engagement' as feature,
                AVG(CASE 
                    WHEN u.last_spin_date IS NULL THEN 70
                    WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 50
                    ELSE 10 
                END) as importance,
                'Gamification engagement correlates with retention' as insight
            FROM users u WHERE u.role = 'customer'
        ";
        
        try {
            $stmt = $this->pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Normalize to 100%
            $total = array_sum(array_column($results, 'importance'));
            foreach ($results as &$row) {
                $row['importance'] = $total > 0 ? round(($row['importance'] / $total) * 100, 1) : 0;
            }
            
            // Sort by importance
            usort($results, function($a, $b) {
                return $b['importance'] <=> $a['importance'];
            });
            
            return $results;
        } catch (PDOException $e) {
            error_log("Feature importance error: " . $e->getMessage());
            return [];
        }
    }
}
?>