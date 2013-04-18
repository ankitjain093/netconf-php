<?php

/**
*Describes exceptions related to commit operation
*/
class CommitException extends Exception {

    public function CommitException($msg) {
         Exception::__construct($msg);
    }

    public function getErrorMessage() {
        return $this->getMessage();
    }
}

?>
