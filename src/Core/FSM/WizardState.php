<?php

namespace App\Core\FSM;

class WizardState
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../../storage/fsm_states.json';
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    private function readData(): array
    {
        $content = file_get_contents($this->filePath);
        return $content ? json_decode($content, true) : [];
    }

    private function writeData(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function get(int $userId): ?array
    {
        $data = $this->readData();
        return $data[$userId] ?? null;
    }

    public function set(int $userId, string $step, array $payload = []): void
    {
        $data = $this->readData();
        $data[$userId] = [
            'step' => $step,
            'payload' => $payload,
            'updated_at' => time()
        ];
        
        // Cleanup old states (> 1 hour)
        foreach ($data as $id => $state) {
            if (isset($state['updated_at']) && (time() - $state['updated_at'] > 3600)) {
                unset($data[$id]);
            }
        }

        $this->writeData($data);
    }

    public function clear(int $userId): void
    {
        $data = $this->readData();
        if (isset($data[$userId])) {
            unset($data[$userId]);
            $this->writeData($data);
        }
    }
}
