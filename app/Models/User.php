<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_KAJUR = 'kajur';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_PRINCIPAL = 'principal';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_MENTOR,
        self::ROLE_KAJUR,
        self::ROLE_TEACHER,
        self::ROLE_PRINCIPAL,
        self::ROLE_SUPER_ADMIN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nis',
        'email',
        'role',
        'avatar_url',
        'permissions_json',
        'kajur_major_name',
        'kajur_red_flag_days',
        'teacher_class_name',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'permissions_json' => 'array',
            'kajur_red_flag_days' => 'integer',
            'password' => 'hashed',
        ];
    }
}
