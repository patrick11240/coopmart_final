<?php
$host = 'sql111.infinityfree.com';   // Your MySQL host
$db_name = 'if0_40918018_coopmart'; // Your database name
$username = 'if0_40918018';          // Your MySQL username
$password = 'gxeNJavlH8';           // Your MySQL password
$port = 3306;                        // Optional, default is 3306



try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Also create $db for backward compatibility
    $db = $pdo;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// User class
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if email exists and return user data
    public function emailExists($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, password_hash, name, membership_type, address, phone_number, created_at FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Error checking user email: " . $e->getMessage());
            return false;
        }
    }

    // Create new user
    public function createUser($email, $password_hash, $name = '', $membership_type = 'non_member', $address = '', $phone_number = '') {
        try {
            $stmt = $this->conn->prepare("INSERT INTO users (email, password_hash, name, membership_type, address, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$email, $password_hash, $name, $membership_type, $address, $phone_number]);
        } catch(PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    // Get user by ID
    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, name, membership_type, address, phone_number, created_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            return false;
        }
    }

    // Update user password
    public function updatePassword($email, $password_hash) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            return $stmt->execute([$password_hash, $email]);
        } catch(PDOException $e) {
            error_log("Error updating user password: " . $e->getMessage());
            return false;
        }
    }

    // Update user profile
    public function updateProfile($id, $name, $address, $phone_number) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET name = ?, address = ?, phone_number = ? WHERE id = ?");
            return $stmt->execute([$name, $address, $phone_number, $id]);
        } catch(PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }

    // Update membership type
    public function updateMembershipType($id, $membership_type) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET membership_type = ? WHERE id = ?");
            return $stmt->execute([$membership_type, $id]);
        } catch(PDOException $e) {
            error_log("Error updating membership type: " . $e->getMessage());
            return false;
        }
    }
}

// Admin class
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if admin email exists
    public function emailExists($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, password, created_at, updated_at FROM admins WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Error checking admin email: " . $e->getMessage());
            return false;
        }
    }

    // Create new admin
    public function createAdmin($email, $password) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
            return $stmt->execute([$email, $password]);
        } catch(PDOException $e) {
            error_log("Error creating admin: " . $e->getMessage());
            return false;
        }
    }

    // Get admin by ID
    public function getAdminById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, created_at, updated_at FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Error getting admin by ID: " . $e->getMessage());
            return false;
        }
    }

    // Update admin password
    public function updatePassword($email, $password) {
        try {
            $stmt = $this->conn->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE email = ?");
            return $stmt->execute([$password, $email]);
        } catch(PDOException $e) {
            error_log("Error updating admin password: " . $e->getMessage());
            return false;
        }
    }

    // Update last login
    public function updateLastLogin($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE admins SET updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    // Get all admins (for management purposes)
    public function getAllAdmins() {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, created_at, updated_at FROM admins ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Error getting all admins: " . $e->getMessage());
            return [];
        }
    }

    // Delete admin
    public function deleteAdmin($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM admins WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            error_log("Error deleting admin: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize User and Admin objects globally for easy access
$user = new User($pdo);
$admin = new Admin($pdo);
?>