@php
    $editing = isset($article);
    $article = $article ?? null;
    $topics = \App\Models\Article::whereNotNull('tag')->where('tag', '!=', '')->distinct()->orderBy('tag')->pluck('tag');
@endphp
<form method="POST" action="{{ $editing ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-12"><label class="form-label fw-medium">{{ __('Title') }} <span class="text-danger">*</span></label><input name="title" value="{{ old('title', $article->title ?? '') }}" required maxlength="180" autofocus class="form-control @error('title') is-invalid @enderror">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Topic') }}</label><input name="tag" list="arTopics" maxlength="60" value="{{ old('tag', $article->tag ?? '') }}" class="form-control" placeholder="{{ __('e.g. Wellness, Skin care') }}"><datalist id="arTopics">@foreach ($topics as $t)<option value="{{ $t }}">@endforeach</datalist></div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Author') }}</label><input name="author" maxlength="80" value="{{ old('author', $article->author ?? '') }}" class="form-control" placeholder="{{ __('e.g. Dr. Sana, Pharmacist') }}"></div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Publish date') }}</label><input type="datetime-local" name="published_at" value="{{ old('published_at', ($article->published_at ?? now())->format('Y-m-d\TH:i')) }}" class="form-control figure @error('published_at') is-invalid @enderror"><div class="form-text">{{ __('A future date schedules it.') }}</div></div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Short summary') }}</label><textarea name="excerpt" rows="2" maxlength="400" class="form-control">{{ old('excerpt', $article->excerpt ?? '') }}</textarea><div class="form-text">{{ __('Shown on cards. Leave empty to use the start of the article.') }}</div></div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Article') }} <span class="text-danger">*</span></label><textarea name="body" rows="14" required class="form-control @error('body') is-invalid @enderror">{{ old('body', $article->body ?? '') }}</textarea>@error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">{{ __('Plain text. A blank line starts a new paragraph; "# Heading", "- list item" and "1. step" make headings and lists.') }}</div></div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Cover image') }}</label>
            @if ($editing && $article->coverUrl())<div class="mb-2 d-flex align-items-center gap-3"><img src="{{ $article->coverUrl() }}" alt="" style="height:80px;border-radius:12px"><div class="form-check"><input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="arRm"><label class="form-check-label" for="arRm">{{ __('Remove image') }}</label></div></div>@endif
            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-control @error('cover') is-invalid @enderror">@error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_published" value="1" id="arPub" @checked(old('is_published', $article->is_published ?? true))><label class="form-check-label" for="arPub">{{ __('Published') }}</label></div></div>
    </div>
    <div class="mt-3 pt-3" style="border-top:1px dashed var(--border)">
        <div class="small text-muted mb-2"><i class="bi bi-search me-1"></i>{{ __('Search engines (optional) — what Google shows for this page. Leave empty to use the name and description.') }}</div>
        <div class="row g-3">
            <div class="col-12"><label class="form-label">{{ __('Search title') }}</label><input name="meta_title" maxlength="120" value="{{ old('meta_title', $article->meta_title ?? '') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">{{ __('Search description') }}</label><textarea name="meta_description" rows="2" maxlength="300" class="form-control">{{ old('meta_description', $article->meta_description ?? '') }}</textarea></div>
        </div>
    </div>
    @include('partials.translation-fields', ['model' => $article, 'fields' => ['title' => 'Title', 'tag' => 'Topic', 'excerpt' => ['Short summary', 'textarea'], 'body' => ['Article', 'textarea']]])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
