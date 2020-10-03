<?php

namespace App;

use Exception;
use Iterator;

/**
 * Class Parser
 *
 * Loads CSV file into memory and can be iterable
 *
 * @package App
 */
class Parser implements Iterator
{

    private array $file;

    private int $position;

    /** @var string[] There is a mapping array, which is used to format data to result array */
    private array $mapping;

    /** @var string[] There are headers from the CSV */
    private array $headers;

    /**
     * Parser constructor.
     * @param string $path
     * @param string $mappingPath
     * @throws Exception
     */
    public function __construct(string $path, string $mappingPath)
    {
        $fileHandle = fopen($path, 'r');
        if (false === $fileHandle) {
            throw new Exception('Error reading CSV file');
        }
        $data = fgetcsv($fileHandle);
        if (!is_array($data)) {
            throw new Exception('Can\'t read headers');
        }
        $this->headers = $data;
        $this->loadFile($fileHandle);
        fclose($fileHandle);
        $this->position = 0;
        if (empty($mapping = file_get_contents($mappingPath))) {
            throw new Exception('Failed to get mapping');
        }
        $this->mapping = json_decode($mapping, true);
        if (json_last_error()) {
            throw new Exception('Failed decode JSON for mapping: ' . json_last_error_msg());
        }
    }

    /**
     * @param resource $handle
     */
    private function loadFile($handle): void
    {
        while (false !== ($line = fgetcsv($handle))) {
            $data = array_map('trim', $line);
            if (empty($data)) {
                continue;
            }
            $this->file[] = $this->getPreparedArray($data);
        }
    }

    private function getPreparedArray(array $data): array
    {
        $preparedData = [];
        foreach ($this->headers as $id => $column) {
            $preparedData['{' . $column . '}'] = empty($data[$id])
                ? ''
                : $data[$id];
        }
        $resultData = [];
        foreach ($this->mapping as $key => $value) {
            $resultData[$key] = str_replace(array_keys($preparedData), array_values($preparedData), $value);
        }
        return $resultData;
    }

    public function current()
    {
        return $this->file[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->file[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

}
