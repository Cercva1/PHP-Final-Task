<?php 

class PasswordGenerator{
    public $length;
    public $numUpper;
    public $numLower;
    public $numDigits;
    public $numSpecial;

    public function generate(): string{
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $special = '!@#$%^&*()-_=+<>?';


        $password = '';
        $password .= $this->randomChars($upper, $this->numUpper);
        $password .= $this->randomChars($lower, $this->numLower);
        $password .= $this->randomChars($digits, $this->numDigits);
        $password .= $this->randomChars($special, $this->numSpecial);

        $remaining = $this->length - ($this->numUpper + $this->numLower + $this->numDigits + $this->numSpecial);
        $all = $upper . $lower . $digits . $special;

        $password .= $this -> randomChars($all, $remaining);
        return str_shuffle($password);

    }

     private function randomChars($characters, $length) {
        $result = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $max)]; // cryptographically secure random char
        }
        return $result;
    }
  


}


?>