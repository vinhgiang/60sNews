<?php

namespace App\Utils;

class MyEnv
{
    public static function updateEnvFile($data)
    {
        // Read the contents of the .env file
        $envFile = __DIR__ . '/../../.env';
        $contents = file_get_contents($envFile);
        
        // Split the contents into an array of lines
        $lines = explode("\n", $contents);
        
        // Loop through the lines and update the values
        foreach ($lines as &$line) {
            // Skip empty lines and comments
            if (empty($line) || $line[0] == '#') {
                continue;
            }

            // Split each line into key and value
            $parts = explode('=', $line, 2);
            $key = $parts[0];

            // Check if the key exists in the provided data
            if (isset($data[$key])) {
                // Update the value
                $line = $key . '=' . $data[$key];
                unset($data[$key]);
            }
        }
        
        // Append any new keys that were not present in the original file
        foreach ($data as $key => $value) {
            $lines[] = $key . '=' . $value;
        }
        
        // Combine the lines back into a string
        $updatedContents = implode("\n", $lines);
        
        // Write the updated contents back to the .env file
        file_put_contents($envFile, $updatedContents);
    }
}