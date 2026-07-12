<?php

namespace App\Core\Http;

class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json; charset=UTF-8';
        
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        echo $this->content;
    }
}
