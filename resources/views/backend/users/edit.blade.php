@extends('layouts.backend')

@section('title')
    {{ trans('backend.edit_account', ['account' => $user->login]) }}
@endsection

@section('content')
    <h1>{{ trans('backend.edit_account', ['account' => $user->login]) }}</h1>

    <div class="row">
        {{ Form::model($user, ['route' => ['backend.user.update', $user], 'method' => 'put', 'class' => 'form']) }}
            <div class="col-xs-12">
                <div class="well">
                    <button type="submit" class="btn btn-primary">
                        {{ trans('backend.save') }}
                    </button>
                    <a href="{{ route('backend.user.index') }}" class="btn btn-default">
                        {{ trans('backend.close') }}
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ trans('backend.account') }}</h3>
                    </div>
                    <div class="panel-body">
                        {{ Form::bsText('login') }}
                        {{ Form::bsEmail('email') }}
                        {{ Form::bsToggle('active', '1', $user->active, ['data-on' => trans('backend.active'), 'data-off' => trans('backend.inactive'), 'data-onstyle' => 'success']) }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            {{ trans('backend.password') }}
                            <span class="pull-right">
                                <button type="button" class="btn btn-primary btn-xs" id="btnEnablePasswordChange">
                                    {{ trans('backend.enable_password_change') }}
                                </button>
                            </span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        {{ Form::bsPassword('password', ['readonly' => true]) }}
                        {{ Form::bsPassword('password_confirmation', ['readonly' => true]) }}
                        <button type="button" class="btn btn-default" id="btnGeneratePassword" disabled="disabled">
                            {{ trans('backend.generate_random_password') }}
                        </button>
                        {{ Form::bsCheckbox('send_password_email') }}
                        {{ Form::bsCheckbox('force_password_change', '1', $user->force_password_change) }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ trans('backend.info') }}</h3>
                    </div>
                    <div class="panel-body">
                        {{ Form::bsText('created_at', $user->created_at->formatLocalized('%c'), ['readonly' => true]) }}
                        {{ Form::bsText('updated_at', $user->updated_at->formatLocalized('%c'), ['readonly' => true]) }}
                        @permission('backend.manage.members')
                            @if ($user->member !== null)
                                <a href="{{ route('backend.member.edit', $user->member) }}">
                                    <span class="glyphicon glyphicon-link"></span>
                                    {{ trans('backend.related_member') }}:
                                    {{ $user->member->getFullName() }}
                                </a>
                            @endif
                        @endpermission
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ trans('backend.roles_and_permissions') }}</h3>
                    </div>
                    <div class="panel-body">
                        {{ Form::bsSelect('roles', $roles, $currentRoles, ['multiple' => true, 'data-live-search' => 'true']) }}
                        <div class="form-group">
                            <label for="permissions">{{ trans('validation.attributes.permissions') }}</label>
                            <select id="permissions" name="permissions[]" class="form-control selectpicker" aria-describedby="permissionsHelpBlock" data-live-search="true" data-actions-box="true" multiple>
                                @foreach($permissions as $label => $group)
                                    <optgroup label="{{ $label }}">
                                        @foreach ($group as $key => $item)
                                            <option value="{{ $key }}"{{ in_array($key, $currentPermissions) ? ' selected' : '' }}>
                                                {{ $item }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" class="btn btn-default btn-block" data-toggle="modal" data-target="#effectivePermissionsModal">
                            <span class="glyphicon glyphicon-info-sign"></span> {{ trans('backend.effective_permissions') }}
                        </button>
                    </div>
                </div>
            </div>
        {{ Form::close() }}
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{{ trans('backend.profile_picture') }}</h3>
                </div>
                <div class="panel-body">
                    @if (! $user->picture)
                        {{ Form::open(['route' => ['backend.user.picture.upload', $user], 'class' => 'dropzone', 'id' => 'profile-picture-dropzone']) }}
                        <div class="fallback">
                            <input name="file" type="file">
                        </div>
                        {{ Form::close() }}
                    @else
                        <p>
                            <img src="{{ route('image', $user) }}" alt="" class="img-responsive img-rounded">
                        </p>
                        {{ Form::open(['route' => ['backend.user.picture.delete', $user], 'method' => 'delete']) }}
                        <button type="button" class="btn btn-danger btn-block"
                                onclick="confirm('{{ trans('backend.really_delete_profile_picture') }}') &amp;&amp; form.submit();">
                            <span class="glyphicon glyphicon-trash"></span>
                            {{ trans('backend.delete_profile_picture') }}
                        </button>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="effectivePermissionsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('backend.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <span class="glyphicon glyphicon-info-sign"></span>
                        {{ trans('backend.effective_permissions') }}
                    </h4>
                </div>
                <div class="modal-body">
                    <ul>
                        @forelse($effectivePermissions as $group => $groups)
                            <li>
                                <strong>{{ $group }}</strong>
                                <ul>
                                    @foreach($groups as $permission)
                                        <li>{{ $permission }}</li>
                                    @endforeach
                                </ul>
                            </li>
                        @empty
                            <li><em>{{ trans('backend.has_no_permissions') }}</em></li>
                        @endforelse
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('backend.close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $("#btnGeneratePassword").click(function () {
            if ($("[name='password']").attr('readonly')) {
                return;
            }
            var chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ023456789";
            var password = "";
            var length = 8;
            for (i = 0; i < length; i++) {
                x = Math.floor(Math.random() * chars.length);
                password += chars[x];
            }
            $("[name='password']").val(password);
            $("[name='password_confirmation']").val(password);
        });

        $("#btnEnablePasswordChange").click(function () {
            $("[name='password']").removeAttr('readonly');
            $("[name='password_confirmation']").removeAttr('readonly');
            $("#btnGeneratePassword").removeAttr('disabled');
            $(this).attr("disabled", true);
        })

        $(document).ready(function () {
            Dropzone.options.profilePictureDropzone = {
                maxFilesize: 8,
                dictDefaultMessage: '{{ trans('backend.drop_pictures_here') }}',
                dictFileTooBig: '{{ trans('backend.file_too_big', ['size' => '8']) }}',
                success: function(file, done) {
                    window.location.assign('{{ route('backend.user.picture.cropform', $user) }}');
                }
            }
        });
    </script>
@endpush

@include('components.tool.select')
@include('components.tool.toggle')
@include('components.tool.dropzone')
