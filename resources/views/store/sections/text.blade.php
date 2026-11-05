<section class="sf-section sf-reveal">
    @if ($s->text('title'))<h2 class="mb-3">{{ $s->text('title') }}</h2>@endif
    <div class="sf-prose">{!! nl2br(e($s->text('body'))) !!}</div>
</section>
