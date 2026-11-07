@extends('errors.minimal')
@section('code', '403')
@section('title', 'Access denied')
@section('message', $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Your role does not have permission to view this page or perform this action.')
