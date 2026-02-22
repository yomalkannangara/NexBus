<?php
namespace App\controllers;

use App\models\common\MessageModel;

class MessageController extends BaseController
{
    public function inbox(): void {
        $this->requireLogin();
        $m = new MessageModel();
        $rows = $m->inboxForUser((int)($_SESSION['user']['user_id'] ?? 0));
        $this->view('support', 'messages_inbox', ['messages' => $rows]);
    }

    public function compose(): void {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $m = new MessageModel();
            $sender = (int)($_SESSION['user']['user_id'] ?? 0);
            $subject = $_POST['subject'] ?? '';
            $body = $_POST['body'] ?? '';
            $scope = $_POST['scope'] ?? 'user';
            $scopeValue = $_POST['scope_value'] ?? null;
            $recipients = array_map('intval', explode(',', $_POST['recipients'] ?? ''));
            $mid = $m->createMessage($sender, $subject, $body, $scope, $scopeValue);
            if (!empty($recipients)) $m->addRecipients($mid, $recipients);
            $this->redirect('/messages/inbox');
        }
        $this->view('support', 'messages_compose', []);
    }

    public function markRead(): void {
        $this->requireLogin();
        $id = (int)($_POST['recipient_id'] ?? 0);
        if ($id) {
            $m = new MessageModel();
            $m->markRead($id);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false], 400);
    }
}
