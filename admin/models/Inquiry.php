<?php

class Inquiry extends LeadModel
{
    protected const TABLE = 'inquiries';
    public const DEFAULT_STATUS = 'new';

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'in_progress' => 'In Progress',
        'follow_up' => 'Follow-up Required',
        'converted' => 'Converted',
        'closed' => 'Closed',
        'not_interested' => 'Not Interested',
        'spam' => 'Spam',
    ];

    public static function create(array $d): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO inquiries (name, phone, email, subject, message, status, ip)
             VALUES (:name, :phone, :email, :subject, :message, :status, :ip)'
        );
        $stmt->execute([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? '',
            'email' => $d['email'] ?? '',
            'subject' => $d['subject'] ?? '',
            'message' => $d['message'],
            'status' => self::DEFAULT_STATUS,
            'ip' => $d['ip'] ?? '',
        ]);
        return (int) self::db()->lastInsertId();
    }
}
