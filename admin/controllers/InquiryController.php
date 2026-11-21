<?php

class InquiryController extends LeadController
{
    protected string $model = Inquiry::class;
    protected string $key = 'inquiry';
    protected string $plural = 'inquiries';
    protected string $title = 'Inquiry Management';
    protected string $subtitle = 'Every inquiry that has come in through the website.';
    protected string $noun = 'inquiry';
}
