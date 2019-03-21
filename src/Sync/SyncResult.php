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
     * @var array
     */
    private $wontProcess = array();

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
     * Returns transactions that were sync during previous sync runs
     *
     * @return array
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

    /**
     * Returns transactions that was not intended to be processed (not suitable for sync)
     *
     * @return array
     */
    public function getWontProcess(): array
    {
        return $this->wontProcess;
    }

    /**
     * @param $skipped
     */
    public function setWontProcess($wontProcess): void
    {
        $this->wontProcess = $wontProcess;
    }

    public function addSkipped($id, $data): void
    {
        $this->skipped[$id] = $data;
    }

    public function addWontProcess($id, $data): void
    {
        $this->wontProcess[$id] = $data;
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