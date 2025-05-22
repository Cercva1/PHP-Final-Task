<?php 

class User {
    private $conn;
    public $id;
    public $username;
    public $password;
    public $aes_key_encrypted;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO users (username, password_hash, aes_key_encrypted) VALUES (:username, :password_hash, :aes_key_encrypted)";
        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($this->password, PASSWORD_DEFAULT); // hash the password

        $key = openssl_random_pseudo_bytes(32); // generate 256-bit AES key
        $iv = openssl_random_pseudo_bytes(16); // generate IV for encryption
        $encrypted_key = openssl_encrypt($key, 'aes-256-cbc', $this->password, 0, $iv); // encrypt key using user password
        
        $combined = $encrypted_key . ':' . base64_encode($iv); // ✔️ store key and IV together in a variable
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":aes_key_encrypted", $combined);

        return $stmt->execute();
    }

    public function login() {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($this->password, $row['password_hash'])) { // verify user password
                $this->id = $row['id'];
                $this->aes_key_encrypted = $row['aes_key_encrypted'];
                return true;
            }
        }
        return false;
    }
}



?>