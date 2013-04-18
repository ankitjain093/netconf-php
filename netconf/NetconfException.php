<?php

/**
*Describes exceptions related to establishing Netocnf session.
*/
class NetconfException extends Exception {
    
    public function NetconfException($msg) {
        Exception::__construct($msg);
    }

    public function getErrorMessage() {
        return $this->getMessage();
    }
}

?>
