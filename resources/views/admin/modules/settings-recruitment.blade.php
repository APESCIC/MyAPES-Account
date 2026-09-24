@extends('layouts.app')

@section('title', 'Recruitment settings | MyAPES Core')

@section('content')
    @include('admin._navigation')

    <header class="page-heading module-settings-heading">
        <div>
            <p class="eyebrow">{{ strtoupper(str_replace('-', ' ', $subCoreKey)) }} · RECRUITMENT</p>
            <h1>Recruitment settings</h1>
            <p>Control the public roles board and whether visitors can apply for open roles.</p>
        </div>
        <div class="module-settings-toolbar actions">
            <a class="button button-secondary" href="{{ route('admin.modules.index') }}">Back to plugins</a>
            @if($canManage)
                <button type="submit" form="module-settings-form">Save settings</button>
            @endif
        </div>
    </header>

    @if(session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    <form
        id="module-settings-form"
        method="post"
        action="{{ route('admin.modules.settings.update', [$subCoreKey, $moduleKey]) }}"
        class="module-settings-editor"
    >
        @csrf
        @method('put')
        <input type="hidden" name="version" value="{{ $record->lock_version }}">

        <section class="module-settings-group">
            <div class="module-settings-group__header">
                <h2>Public board</h2>
                <p class="muted">When off, the public roles board and discovery links are hidden.</p>
            </div>
            <label class="module-settings-toggle">
                <input
                    type="checkbox"
                    name="public_board_enabled"
                    value="1"
                    @checked(old('public_board_enabled', $settings['public_board_enabled'] ?? true))
                    @disabled(! $canManage)
                >
                Show public roles board
            </label>
        </section>

        <section class="module-settings-group">
            <div class="module-settings-group__header">
                <h2>Public apply</h2>
                <p class="muted">When off, signed-in visitors can browse open roles but cannot submit applications. Requires the public board to be on.</p>
            </div>
            <label class="module-settings-toggle">
                <input
                    type="checkbox"
                    name="public_apply_enabled"
                    value="1"
                    @checked(old('public_apply_enabled', $settings['public_apply_enabled'] ?? true))
                    @disabled(! $canManage)
                >
                Allow public applications
            </label>
        </section>

        @if($canManage)
            <div class="module-settings-sticky-actions">
                <button type="submit">Save settings</button>
            </div>
        @endif
    </form>

    @if($canManage)
        <form
            method="post"
            action="{{ route('admin.modules.settings.update', [$subCoreKey, $moduleKey]) }}"
            class="module-settings-reset"
            onsubmit="return confirm('Reset Recruitment settings to defaults?')"
        >
            @csrf
            @method('put')
            <input type="hidden" name="version" value="{{ $record->lock_version }}">
            <input type="hidden" name="reset_defaults" value="1">
            <p class="muted">Restore public board and apply toggles to their default (both on).</p>
            <label>
                <input type="checkbox" name="confirm_reset" value="1" required>
                Confirm reset to defaults
            </label>
            <button type="submit" class="button button-secondary">Reset to defaults</button>
        </form>
    @endif
@endsection
