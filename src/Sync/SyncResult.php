<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace App\Sync;

class SyncResult
{
    /**
     * @var array
     */
    private $imported = array();

    /**
     * @var array
     */
    private $skipped = array();

    /**
     * @var string[]
     */
    private $errors = array();

    /**
     * @return int
     */
    public function getImported(): array
    {
        return $this->imported;
    }

    /**
     * @param $imported
     */
    public function setImported($imported): void
    {
        $this->imported = $imported;
    }

    public function addImported($id, $data): void
    {
        $this->imported[$id] = $data;
    }

    /**
     * @return int
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /**
     * @param $skipped
     */
    public function setSkipped($skipped): void
    {
        $this->skipped = $skipped;
    }

    public function addSkipped($id, $data): void
    {
        $this->skipped[$id] = $data;
    }
    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string[] $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @param $id
     * @param mixed $data
     */
    public function addError($id, $data)
    {
        if (!isset($this->errors[$id])) {
            $this->errors[$id] = [];
        }
        $this->errors[$id][] = $data;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}