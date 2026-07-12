<?php

namespace App\Core\Localization;

class Lang
{
    private static ?array $messages = null;

    public static function get(string $key, array $replacements = []): string
    {
        if (self::$messages === null) {
            self::$messages = require __DIR__ . '/../../../lang/uz.php';
        }

        $text = self::$messages[$key] ?? $key;

        foreach ($replacements as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', (string) $value, $text);
        }

        return $text;
    }
}
