<?php 

class PasswordStorage {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function save($user_id, $service, $password, $key) {
        $iv = openssl_random_pseudo_bytes(16); // new IV for each password
        $encrypted_password = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv); // encrypt password
        $combined = $encrypted_password . ':' . base64_encode($iv); // store both encrypted password and IV

        $query = "INSERT INTO passwords (user_id, service_name, password_encrypted) VALUES (:user_id, :service, :password)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":service", $service);
        $stmt->bindParam(":password", $combined);

        return $stmt->execute();
    }

    public function fetchAll($user_id) {
        $query = "SELECT * FROM passwords WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // return all user password records
    }
}

?>