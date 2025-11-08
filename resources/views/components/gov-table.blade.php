{{-- Reusable government-style table shell component --}}
{{-- Usage:
<x-gov-table :headers="['Name','Email','Registered','']" tableId="users-table"
    headerClass="bg-gray-50" headerTextClass="text-gray-500">
    @foreach($users as $user)
        @include('admin.users._row',['user'=>$user])
    @endforeach
</x-gov-table>
Props:
- headers: array of header labels (strings)
- tableId: optional id attribute for the table
- headerAlign: text-left|text-center (default text-left)
- headerClass: class for the thead background (default bg-purple-600)
- headerTextClass: text color class for headers (default text-white)
- rowHover: bool (default true) — apply hover on rows via tbody arbitrary variant
--}}
@props([
    'headers' => [],
    'tableId' => null,
    'headerAlign' => 'text-left',
    'headerClass' => 'bg-purple-600',
    'headerTextClass' => 'text-white',
    'rowHover' => true,
])
@php
    $tbodyClasses = 'bg-white divide-y divide-gray-200';
    if ($rowHover) {
        $tbodyClasses .= ' [&>tr:hover]:bg-gray-50';
    }
@endphp
<div class="card">
    <div class="overflow-x-auto">
        <table {{ $tableId ? 'id='.$tableId : '' }} class="min-w-full divide-y divide-gray-200">
            <thead class="{{ $headerClass }}">
                <tr>
                    @foreach($headers as $h)
                        <th class="px-6 py-3 {{ $headerAlign }} text-xs font-medium {{ $headerTextClass }} uppercase tracking-wider">{!! $h !!}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="{{ $tbodyClasses }}">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
