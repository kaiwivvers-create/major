@extends('errors.layout')

@php
    $status = 403;
    $title = 'Access denied';
    $message = 'You do not have permission to open this page. If this should be allowed, ask a super admin to update your role permissions.';
@endphp
