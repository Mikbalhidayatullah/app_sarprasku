@props(['value','kind'=>'condition'])
<span class="badge badge-{{ $value }}">{{ config('inventory.'.match($kind){'condition'=>'conditions','transfer'=>'transfer_statuses',default=>'statuses'}.'.'.$value,$value) }}</span>
