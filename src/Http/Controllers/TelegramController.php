<?php

namespace App\Http\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Telegram\Handlers\UserHandler;
use App\Telegram\Handlers\AdminHandler;
use App\Services\AdminWizardService;
use App\Repositories\UserRepository;
use App\Core\FSM\WizardState;
use App\Http\Middlewares\CheckSubscriptionMiddleware;

class TelegramController
{
    private UserHandler $userHandler;
    private AdminHandler $adminHandler;
    private AdminWizardService $wizardService;
    private UserRepository $userRepository;
    private WizardState $wizardState;
    private CheckSubscriptionMiddleware $subMiddleware;

    public function __construct(
        UserHandler $userHandler,
        AdminHandler $adminHandler,
        AdminWizardService $wizardService,
        UserRepository $userRepository,
        WizardState $wizardState,
        CheckSubscriptionMiddleware $subMiddleware
    ) {
        $this->userHandler = $userHandler;
        $this->adminHandler = $adminHandler;
        $this->wizardService = $wizardService;
        $this->userRepository = $userRepository;
        $this->wizardState = $wizardState;
        $this->subMiddleware = $subMiddleware;
    }

    public function handle(Request $request): Response
    {
        $update = $request->all();

        if (!isset($update['message']) && !isset($update['callback_query'])) {
            return (new Response())->json(['status' => 'ok']);
        }

        $isCallback = isset($update['callback_query']);
        $message = $isCallback ? $update['callback_query']['message'] : $update['message'];
        
        $chatId = $message['chat']['id'] ?? 0;
        $userId = $isCallback ? $update['callback_query']['from']['id'] : ($message['from']['id'] ?? 0);
        $text = $isCallback ? $update['callback_query']['data'] : trim($message['text'] ?? '');

        // Callback "check_sub" bo'lsa
        if ($isCallback && $text === 'check_sub') {
            if (!$this->subMiddleware->check($userId)) {
                // Hali ham obuna bo'lmagan
                return (new Response())->json(['status' => 'ok']);
            }
            // Obuna bo'lgan bo'lsa, /start bosgandek muomala qilamiz
            $message['text'] = '/start';
            $text = '/start';
        }

        $userDb = $this->userRepository->findByTelegramId($userId);

        // Env dan adminlarni tekshirish
        $adminIdsStr = $_ENV['ADMIN_IDS'] ?? '';
        $adminIds = array_map('trim', explode(',', $adminIdsStr));
        $isAdminByEnv = in_array((string)$userId, $adminIds);

        if (!$userDb) {
            $from = $isCallback ? $update['callback_query']['from'] : $message['from'];
            $this->userRepository->create($from);
            $userDb = $this->userRepository->findByTelegramId($userId);
        }

        // Agar .env da admin qilingan bo'lsa-yu, bazada oddiy user bo'lsa, bazani yangilaymiz
        if ($isAdminByEnv && !$userDb['is_admin']) {
            $this->userRepository->makeAdmin($userId);
            $userDb['is_admin'] = true;
        }

        // Agar admin bo'lsa
        if ($userDb && $userDb['is_admin']) {
            $text = trim($message['text'] ?? '');
            
            // FSM holatini tekshiramiz
            $currentState = $this->wizardState->get($userId);
            
            // Agar Admin allaqachon kino qo'shish jarayonida bo'lsa Yoki /cancel bossa
            if ($currentState || $text === '/cancel') {
                $this->wizardService->handle($chatId, $userId, $message);
                return (new Response())->json(['status' => 'ok']);
            }
            
            // Agar jarayonda bo'lmasa va admin buyruqlarini yozsa
            if (str_starts_with($text, '/') && !in_array($text, ['/start', '/top', '/help'])) {
                $this->adminHandler->handle($chatId, $userId, $message);
                return (new Response())->json(['status' => 'ok']);
            }
        }

        // Majburiy obuna tekshiruvi
        if (!$this->subMiddleware->check($userId)) {
            $this->subMiddleware->sendSubscriptionWarning($chatId);
            return (new Response())->json(['status' => 'ok']);
        }

        // Agar obuna bo'lsa, davom etadi
        $this->userHandler->handle($chatId, $userId, $message, $userDb);

        return (new Response())->json(['status' => 'ok']);
    }
}
