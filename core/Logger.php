<?php
// core/Logger.php

class Logger {
    const LEVEL_DEBUG   = 0;
    const LEVEL_INFO    = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR   = 3;
    
    private $logFile;
    private $logLevel;
    private static $instance;
    
    // Private constructor to enforce a singleton pattern.
    private function __construct($logFile, $logLevel = self::LEVEL_INFO) {
        $this->logFile  = $logFile;
        $this->logLevel = $logLevel;
    }
    
    // Initialize the logger.
    public static function init($logFile, $logLevel = self::LEVEL_INFO) {
        if (!self::$instance) {
            self::$instance = new Logger($logFile, $logLevel);
        }
        return self::$instance;
    }
    
    // Retrieve the logger instance.
    public static function getInstance() {
        if (!self::$instance) {
            throw new Exception("Logger not initialized.");
        }
        return self::$instance;
    }
    
    // General log method.
    public function log($level, $message, $context = []) {
        if ($level < $this->logLevel) {
            return; // Skip logging if the message level is lower than our threshold.
        }
        $date      = date('Y-m-d H:i:s');
        $levelName = $this->getLevelName($level);
        // Format context data as JSON for clarity.
        $contextString = !empty($context) ? json_encode($context) : '';
        $formattedMessage = "[$date] [$levelName] $message $contextString" . PHP_EOL;
        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }
    
    // Convenience methods.
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    private function getLevelName($level) {
        switch ($level) {
            case self::LEVEL_DEBUG:
                return 'DEBUG';
            case self::LEVEL_INFO:
                return 'INFO';
            case self::LEVEL_WARNING:
                return 'WARNING';
            case self::LEVEL_ERROR:
                return 'ERROR';
            default:
                return 'LOG';
        }
    }
}
