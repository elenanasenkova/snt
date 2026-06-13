<?php
use models\Meeting;

class InfoController {
    public static function index(): void {
        $meetings = Meeting::upcoming();
        renderTemplate('info', ['meetings' => $meetings]);
    }
}
