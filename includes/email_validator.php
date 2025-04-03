<?php
class EmailValidator {
    private $email;
    private $domain;
    private $errorMessage = '';
    private $isValid = false;
    private $mxRecords = [];
    private $validationType = '';
    
    public function __construct($email) {
        $this->email = $email;
        $this->domain = $this->extractDomain($email);
    }
    
    private function extractDomain($email) {
        $parts = explode('@', $email);
        return end($parts);
    }
    
    public function validate() {
        try {
            // Step 1: Basic format validation
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->errorMessage = "Invalid email format";
                $this->validationType = 'format';
                return false;
            }
            
            // Step 2: MX record validation
            if (!$this->checkMXRecords()) {
                $this->validationType = 'mx';
                return false;
            }
            
            // Step 3: SMTP validation
            if (!$this->checkSMTP()) {
                $this->validationType = 'smtp';
                return false;
            }
            
            $this->isValid = true;
            $this->validationType = 'complete';
            return true;
            
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }
    
    private function checkMXRecords() {
        // Check if domain has MX records
        $this->mxRecords = [];
        
        if (!getmxrr($this->domain, $mxhosts, $mxweight)) {
            $this->errorMessage = "No MX records found for the domain: {$this->domain}";
            return false;
        }
        
        // Store MX records
        $mx_priority = [];
        for ($i = 0; $i < count($mxhosts); $i++) {
            $mx_priority[$mxhosts[$i]] = $mxweight[$i];
            $this->mxRecords[] = ['host' => $mxhosts[$i], 'weight' => $mxweight[$i]];
        }
        
        // Sort by priority
        asort($mx_priority);
        
        return true;
    }
    
    private function checkSMTP() {
        if (empty($this->mxRecords)) {
            $this->errorMessage = "No MX records available for SMTP check";
            return false;
        }
        
        // Get the MX record with lowest priority
        $mailServer = $this->mxRecords[0]['host'];
        
        try {
            // Try to open a connection to the mail server
            $connection = @fsockopen($mailServer, 25, $errno, $errstr, 5);
            if (!$connection) {
                $this->errorMessage = "Could not connect to mail server: $errstr ($errno)";
                return false;
            }
            
            // SMTP communication
            $response = fgets($connection);
            if (substr($response, 0, 3) != '220') {
                $this->errorMessage = "Invalid response from mail server: $response";
                fclose($connection);
                return false;
            }
            
            // Say HELO
            fputs($connection, "HELO example.com\r\n");
            $response = fgets($connection);
            if (substr($response, 0, 3) != '250') {
                $this->errorMessage = "Invalid HELO response: $response";
                fclose($connection);
                return false;
            }
            
            // MAIL FROM
            fputs($connection, "MAIL FROM: <test@example.com>\r\n");
            $response = fgets($connection);
            if (substr($response, 0, 3) != '250') {
                $this->errorMessage = "Invalid MAIL FROM response: $response";
                fclose($connection);
                return false;
            }
            
            // RCPT TO - check if the email is accepted
            fputs($connection, "RCPT TO: <{$this->email}>\r\n");
            $response = fgets($connection);
            
            // Close the connection
            fputs($connection, "QUIT\r\n");
            fclose($connection);
            
            // Check if the email is valid based on the SMTP response
            if (substr($response, 0, 3) == '250') {
                return true;
            } else {
                $this->errorMessage = "Email address was rejected by the mail server: $response";
                return false;
            }
            
        } catch (Exception $e) {
            $this->errorMessage = "SMTP check failed: " . $e->getMessage();
            return false;
        }
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    public function isValid() {
        return $this->isValid;
    }
    
    public function getMxRecords() {
        return $this->mxRecords;
    }
    
    public function getMxRecordsFormatted() {
        $formatted = '';
        foreach ($this->mxRecords as $record) {
            $formatted .= "Host: {$record['host']}, Priority: {$record['weight']}<br>";
        }
        return $formatted ? $formatted : 'No MX records found';
    }
    
    public function getValidationType() {
        return $this->validationType;
    }
    
    public function getMxRecordsJson() {
        return json_encode($this->mxRecords);
    }
}

// Function to save validation result to database
function saveValidationToDatabase($pdo, $email, $isValid, $validationType, $errorMessage, $mxRecords) {
    try {
        $sql = "INSERT INTO email_validations (email, is_valid, validation_type, error_message, mx_records) 
                VALUES (:email, :is_valid, :validation_type, :error_message, :mx_records)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':is_valid', $isValid, PDO::PARAM_BOOL);
        $stmt->bindParam(':validation_type', $validationType);
        $stmt->bindParam(':error_message', $errorMessage);
        $stmt->bindParam(':mx_records', $mxRecords);
        
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// User-related validation functions are now in includes/auth.php
?>
