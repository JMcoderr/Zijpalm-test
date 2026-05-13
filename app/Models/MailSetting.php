<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    // English comments: Model used to persist mail batch/delay settings per form/modal.
    protected $table = 'mail_settings';

    protected $fillable = [
        'name',
        'batch_size',
        'delay',
    ];
}
