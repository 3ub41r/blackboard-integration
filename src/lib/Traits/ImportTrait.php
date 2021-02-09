<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ImportTrait
{
    const SEPARATOR = '|';
    const ENDPOINT = 'https://utmspace.blackboard.com/webapps/bb-data-integration-flatfile-BB5c2d88ecaab71/endpoint';
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
        $output = self::DATA_DIR . "/$feedType.txt";

        if (! $results || empty($results)) {
            echo "No results for $output...\n";
            return null;
        }

        // Create data directory if it does not exist
        if (! file_exists(self::DATA_DIR)) {
            mkdir(self::DATA_DIR, 0777, true);
        }

        echo "Writing to $output...\n";

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
        $client = new Client();

        $url = self::ENDPOINT . "/$feedType/store";

        // Append path and file
        $filePath = join('/', [
            trim(self::DATA_DIR, '/'),
            trim($file, '/'),
        ]);

        echo "Uploading $filePath to $url...\n";

        // POST file
        $response = $client->request('POST', $url, [
            'body' => fopen($filePath, 'r'),
            'auth' => [
                'a2ae284a-e63b-4e50-bbb8-2256ac38a91a', 
                '6N^ltz5@2@a0'
            ],
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
            'debug' => true,
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
        $output = $this->generateFile($results, $feedType);
        $this->uploadFile($output, $feedType);
    }
}