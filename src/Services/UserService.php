<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function findOrCreate(array $fromPayload): array
    {
        $telegramId = $fromPayload['id'];
        
        $user = $this->userRepository->findByTelegramId($telegramId);

        if (!$user) {
            $this->userRepository->create($fromPayload);
            $user = $this->userRepository->findByTelegramId($telegramId);
        }

        return $user;
    }
}
