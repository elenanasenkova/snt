<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

use models\{Document, Ticket};

/**
 * Единая «Канцелярия» (/office): Документы + Обращения в одном разделе.
 * Выделено из MemberController. Реестр членов переехал на страницу
 * «Пользователи» (/admin/users), чтобы не дублировать его.
 */
class OfficeController
{
    /**
     * Роль-зависимо:
     *  - Документы — правление загружает/удаляет, член видит только публичные;
     *  - Обращения — член создаёт/отвечает по своим; правление (isAdmin: 1,2) отвечает и меняет статус.
     * «Единая форма создания» подстраивается под роль: правление → документ, член → обращение.
     */
    public static function office(): void {
        requireAuth();
        Ticket::ensureTable();
        $user   = getCurrentUser();
        $userId = (int)$user['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/office');
            }
            $action = $_POST['action'] ?? '';

            switch ($action) {
                // --- Член создаёт обращение ---
                case 'create_ticket': {
                    $subject = trim($_POST['subject'] ?? '');
                    $body    = trim($_POST['body'] ?? '');
                    if ($subject === '' || $body === '') {
                        flash('Укажите тему и текст обращения.', 'error');
                        redirect('/office?tab=tickets');
                    }
                    $newId = Ticket::create($userId, mb_substr($subject, 0, 200), $body);
                    flash('Обращение отправлено в правление.', 'success');
                    redirect('/office?tab=tickets&id=' . $newId);
                }

                // --- Ответ в обращении (член по своему / правление по любому) ---
                case 'reply_ticket': {
                    $ticketId = (int)($_POST['ticket_id'] ?? 0);
                    $body     = trim($_POST['body'] ?? '');
                    $ticket   = Ticket::find($ticketId);
                    if (!$ticket) { flash('Обращение не найдено.', 'error'); redirect('/office?tab=tickets'); }
                    $isOwner  = (int)$ticket['user_id'] === $userId;
                    if (!$isOwner && !isAdmin()) { flash('Нет прав на это обращение.', 'error'); redirect('/office?tab=tickets'); }
                    if ($ticket['status'] === 'closed') { flash('Обращение закрыто.', 'warning'); redirect('/office?tab=tickets&id=' . $ticketId); }
                    if ($body === '') { flash('Введите текст ответа.', 'error'); redirect('/office?tab=tickets&id=' . $ticketId); }

                    if (isAdmin() && !$isOwner) {
                        // Ответ правления → «Отвечено», уведомить автора
                        Ticket::addReply($ticketId, $userId, $body, 'answered');
                        \models\Notification::create(
                            (int)$ticket['user_id'], 'info',
                            'Ответ на обращение № ' . $ticketId,
                            'Правление ответило на ваше обращение «' . $ticket['subject'] . '».',
                            '/office?tab=tickets&id=' . $ticketId
                        );
                        flash('Ответ отправлен, член уведомлён.', 'success');
                    } else {
                        // Ответ члена → возвращает в работу
                        Ticket::addReply($ticketId, $userId, $body, 'in_progress');
                        flash('Ответ добавлен.', 'success');
                    }
                    redirect('/office?tab=tickets&id=' . $ticketId);
                }

                // --- Смена статуса обращения (только правление) ---
                case 'ticket_status': {
                    if (!isAdmin()) { flash('Недостаточно прав.', 'error'); redirect('/office?tab=tickets'); }
                    $ticketId = (int)($_POST['ticket_id'] ?? 0);
                    $status   = $_POST['status'] ?? '';
                    $ticket   = Ticket::find($ticketId);
                    if ($ticket && in_array($status, Ticket::STATUSES, true)) {
                        Ticket::setStatus($ticketId, $status);
                        \models\Notification::create(
                            (int)$ticket['user_id'], 'info',
                            'Статус обращения № ' . $ticketId . ' изменён',
                            'Новый статус: ' . (Ticket::STATUS_LABELS[$status] ?? $status) . '.',
                            '/office?tab=tickets&id=' . $ticketId
                        );
                        flash('Статус обновлён.', 'success');
                    }
                    redirect('/office?tab=tickets&id=' . $ticketId);
                }

                // --- Загрузка документа (только правление) ---
                case 'create_document': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/office'); }
                    $title    = trim($_POST['title'] ?? '');
                    $allowedCat = ['устав', 'протокол', 'положение', 'смета', 'другое'];
                    $category = in_array($_POST['category'] ?? '', $allowedCat, true) ? $_POST['category'] : 'другое';
                    $isPublic = isset($_POST['is_public']);
                    if ($title === '' || empty($_FILES['file']['tmp_name'])) {
                        flash('Укажите название и выберите файл.', 'error');
                        redirect('/office?tab=docs');
                    }
                    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                        flash('Ошибка загрузки файла.', 'error');
                        redirect('/office?tab=docs');
                    }
                    $origName = $_FILES['file']['name'];
                    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowed  = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','odt','ods'];
                    if (!in_array($ext, $allowed, true)) {
                        flash('Недопустимый формат файла.', 'error');
                        redirect('/office?tab=docs');
                    }
                    if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
                        flash('Файл слишком большой (максимум 20 МБ).', 'error');
                        redirect('/office?tab=docs');
                    }
                    $uploadDir = BASE_PATH . '/public/uploads/documents/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $safeName = preg_replace('/[^a-z0-9_.-]/i', '_', pathinfo($origName, PATHINFO_FILENAME));
                    $fileName = date('Ymd_His') . '_' . $safeName . '.' . $ext;
                    if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName)) {
                        flash('Не удалось сохранить файл.', 'error');
                        redirect('/office?tab=docs');
                    }
                    Document::create(
                        $title, trim($_POST['description'] ?? ''), $category, $fileName,
                        (int)$_FILES['file']['size'], $_FILES['file']['type'] ?? '', $isPublic, $userId
                    );
                    flash('Документ добавлен.', 'success');
                    redirect('/office?tab=docs');
                }

                // --- Удаление документа (только правление) ---
                case 'delete_document': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/office'); }
                    Document::delete((int)($_POST['id'] ?? 0));
                    flash('Документ удалён.', 'success');
                    redirect('/office?tab=docs');
                }
            }
            redirect('/office');
        }

        // GET — собрать данные канцелярии
        $manageDocs    = canMeetings();          // правление управляет документами
        $manageTickets = isAdmin();              // правление (1,2) ведёт обращения
        $showTickets   = !canMeetings() || isAdmin(); // член — свои; правление — только админ/председатель

        // Просмотр одной переписки
        $viewId  = (int)($_GET['id'] ?? 0);
        $thread  = null;
        $replies = [];
        if ($viewId > 0) {
            $t = Ticket::find($viewId);
            $canView = $t && ((int)$t['user_id'] === $userId || isAdmin());
            if (!$canView) {
                flash('Обращение не найдено.', 'error');
                redirect('/office?tab=tickets');
            }
            $thread  = $t;
            $replies = Ticket::replies($viewId);
        }

        $documents = $manageDocs ? Document::all() : Document::allPublic();
        $filter    = $_GET['status'] ?? '';
        if (!in_array($filter, Ticket::STATUSES, true)) $filter = '';
        $tickets   = $manageTickets ? Ticket::all($filter) : ($showTickets ? Ticket::forUser($userId) : []);

        // Активная вкладка
        $tab = $_GET['tab'] ?? 'docs';
        if ($viewId > 0) $tab = 'tickets';
        $allowedTabs = array_filter(['docs', $showTickets ? 'tickets' : null]);
        if (!in_array($tab, $allowedTabs, true)) $tab = reset($allowedTabs);

        renderTemplate('member/office', compact(
            'user', 'userId', 'documents', 'tickets', 'thread', 'replies',
            'filter', 'tab', 'manageDocs', 'manageTickets', 'showTickets'
        ) + ['flash' => getFlash()]);
    }
}
