@extends('errors.layout')

@php
    $status = 419;
    $title = 'Session expired';
    $message = 'Your session has expired or the security token is invalid. Please retry the action from a fresh page.';
@endphp
