<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App;

enum UserNotifications: int
{
    case NEWSLETTER = 1;
    case NEW_ACTIVITY = 2;
    case ACTIVITY_REMINDER = 4;
    case RECURRING_ACTIVITY_REMINDER = 8;
    case ACTIVITY_SIGNUP = 16;
    case REPORTS = 32;
}
