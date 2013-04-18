<?php

/**
*Describes exceptions related to load operation
*/
class LoadException extends Exception {
    
    public function LoadException($msg) {
        Exception::__construct($msg);
    }

    public function getErrorMessage() {
        return $this->getMessage();
    }
}

?>
