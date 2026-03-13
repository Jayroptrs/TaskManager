<x-layout>
    <div class="py-8 md:py-12 max-w-6xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('profile.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ __('profile.description') }}</p>

        <form id="profile-update-form" action="/profile" method="POST" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf
            @method('PATCH')
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="space-y-4 rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_97%,white_3%),color-mix(in_srgb,var(--color-input)_16%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">
                    <h2 class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.09em] text-muted-foreground shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">{{ __('profile.account_section') }}</h2>
                    <x-form.field :placeholder="__('profile.name_placeholder')" :label="__('profile.name')" name="name" :value="$user->name" />
                    <x-form.field :placeholder="__('profile.email_placeholder')" :label="__('profile.email')" name="email" type="email" :value="$user->email" />
                </section>

                <section class="space-y-4 rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_97%,white_3%),color-mix(in_srgb,var(--color-input)_16%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">
                    <h2 class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.09em] text-muted-foreground shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">{{ __('profile.password_section') }}</h2>
                    <x-form.field :placeholder="__('profile.current_password_placeholder')" :label="__('profile.current_password')" name="current_password" type="password" />
                    <x-form.field :placeholder="__('profile.new_password_placeholder')" :label="__('profile.new_password')" name="password" type="password" />
                    <x-form.field :placeholder="__('profile.confirm_password_placeholder')" :label="__('profile.confirm_password')" name="password_confirmation" type="password" />
                </section>
            </div>

            <section
                x-data="{ previewUrl: @js($user->avatarUrl()) }"
                class="space-y-4 rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_97%,white_3%),color-mix(in_srgb,var(--color-input)_16%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]"
            >
                <h2 class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.09em] text-muted-foreground shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">{{ __('profile.avatar_section') }}</h2>

                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <img :src="previewUrl" src="{{ $user->avatarUrl() }}" alt="" class="size-16 rounded-full border border-border/80 object-cover">
                    <div class="w-full flex-1 space-y-2">
                        <label for="avatar" class="label">{{ __('profile.avatar') }}</label>
                        <input
                            class="rounded-md border border-border/80 bg-transparent px-3 py-2 text-sm shadow-sm file:border-0 file:bg-transparent file:text-sm file:font-semibold hover:file:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept="image/*"
                            @change="
                                const file = $event.target.files?.[0];
                                if (!file) return;
                                if (previewUrl && previewUrl.startsWith('blob:')) URL.revokeObjectURL(previewUrl);
                                previewUrl = URL.createObjectURL(file);
                            "
                        >
                        <x-form.error name="avatar" />
                    </div>
                </div>
            </section>

        </form>

        <div class="mt-2 py-6 flex flex-wrap justify-end gap-3">
            @if ($user->avatar_path)
                <form action="{{ route('profile.avatar.destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outlined h-10">{{ __('profile.remove_avatar') }}</button>
                </form>
            @endif
            <button type="submit" form="profile-update-form" class="btn h-10">{{ __('profile.save') }}</button>
        </div>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-2 lg:items-stretch">
            <section class="flex h-full flex-col space-y-4 rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_97%,white_3%),color-mix(in_srgb,var(--color-input)_16%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">
                <h2 class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.09em] text-muted-foreground shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">{{ __('profile.onboarding_section') }}</h2>
                <p class="text-sm text-muted-foreground">{{ __('profile.onboarding_description') }}</p>
                <div class="rounded-xl border border-border/70 bg-card/70 px-3 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('profile.onboarding_details_title') }}</p>
                    <ul class="mt-2 space-y-2 text-sm text-foreground/90">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary/80"></span>
                            <span>{{ __('profile.onboarding_point_dashboard') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary/80"></span>
                            <span>{{ __('profile.onboarding_point_tasks') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary/80"></span>
                            <span>{{ __('profile.onboarding_point_collaboration') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary/80"></span>
                            <span>{{ __('profile.onboarding_point_inbox') }}</span>
                        </li>
                    </ul>
                </div>
                <form action="{{ route('onboarding.reset') }}" method="POST" class="mt-auto flex justify-end pt-2">
                    @csrf
                    <button type="submit" class="btn btn-outlined h-10">{{ __('profile.onboarding_reset') }}</button>
                </form>
            </section>

            <section class="flex h-full flex-col space-y-4 rounded-2xl border border-red-500/30 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_97%,white_3%),color-mix(in_srgb,#ef4444_12%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent)]">
                <h2 class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-card/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.09em] text-red-400">{{ __('profile.delete_section') }}</h2>
                <p class="text-sm text-muted-foreground">{{ __('profile.delete_description') }}</p>

                <div class="rounded-xl border border-red-500/25 bg-card/70 px-3 py-3">
                    <ul class="space-y-2 text-sm text-foreground/90">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-red-400/80"></span>
                            <span>{{ __('profile.delete_point_data') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-red-400/80"></span>
                            <span>{{ __('profile.delete_point_tasks') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-red-400/80"></span>
                            <span>{{ __('profile.delete_point_access') }}</span>
                        </li>
                    </ul>
                </div>

                <form action="{{ route('profile.destroy') }}" method="POST" class="mt-auto space-y-3 pt-2">
                    @csrf
                    @method('DELETE')

                    <x-form.field
                        :placeholder="__('profile.delete_password_placeholder')"
                        :label="__('profile.delete_password')"
                        name="current_password"
                        type="password"
                    />
                    <x-form.error name="delete_account" />

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-danger-outlined h-10">{{ __('profile.delete_account') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layout>
