<?php

namespace App\Interfaces;

use GuzzleHttp\Client;

abstract class AbstractImport {
    const SEPARATOR = '|';
    const DATA_DIR = '../data';

    /**
     * Generate file from results and save it to a file in the output location.
     *
     * @param object $results PDO results
     * @param string $output Path to save file
     * @return string
     */
    private function generateFile($results, $feedType)
    {
        $output = self::DATA_DIR . "/{$feedType}_" . date('m-d-Y_H_i_s') . ".txt";

        if (! $results || empty($results)) {
            echo "\nNo results for $output...\n";
            return null;
        }

        echo "\nGenerating feed file for $output (" . sizeof($results) . " rows)\n";

        // Create data directory if it does not exist
        if (! file_exists(self::DATA_DIR)) {
            mkdir(self::DATA_DIR, 0777, true);
        }

        echo "Writing to $output...\n";

        // Column headers
        $text = implode(self::SEPARATOR, array_keys($results[0])) . "\n";

        foreach ($results as $result) {
            // Trim
            foreach ($result as $key => $value) {
                $result[$key] = trim($value);
            }

            $text .= implode(self::SEPARATOR, $result) . "\n";
        }

        file_put_contents($output, $text);

        return $output;
    }

    /**
     * Upload generated file.
     * 
     * Feed types can be: person, course, membership
     *
     * @param string $file Path to generated file
     * @param string $feedType 
     * @return void
     */
    private function uploadFile($file, $feedType)
    {
        $debugEnabled = $_ENV['BLACKBOARD_DEBUG'] === 'true';

        $client = new Client();

        $url = $_ENV['BLACKBOARD_ENDPOINT'] . "/$feedType/store";

        // Append path and file
        $filePath = join('/', [
            trim(self::DATA_DIR, '/'),
            trim($file, '/'),
        ]);

        if ($debugEnabled) {
            echo "Uploading $filePath to $url...\n";
        }

        // POST file
        $response = $client->request('POST', $url, [
            'body' => fopen($filePath, 'r'),
            'auth' => [
                $_ENV['BLACKBOARD_USER'],
                $_ENV['BLACKBOARD_PASSWORD'],
            ],
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
            'debug' => $debugEnabled,
        ]);

        echo $response->getBody();
    }

    /**
     * Generate text file from results and upload to Blackboard
     *
     * @param mixed $results
     * @param string $feedType
     * @return void
     */
    public function upload($results, $feedType)
    {
        if (! $results || empty($results)) {
            echo "\nNothing to upload. Skipping...\n";
            return;
        }

        $output = $this->generateFile($results, $feedType);
        $this->uploadFile($output, $feedType);
    }
    
    // Abstract functions
    public abstract function processLecturers();
    public abstract function processStudents();
    public abstract function processCourses();
    public abstract function processCourseLecturers();
    public abstract function processEnrollments();
}