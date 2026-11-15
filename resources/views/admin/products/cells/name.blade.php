<div class="d-flex align-items-center gap-3">
                        @if ($p->thumbUrl())<img src="{{ $p->thumbUrl() }}" alt="" width="40" height="40" style="object-fit:cover;border-radius:10px">@else<span class="nx-chip-avatar"><i class="bi bi-box-seam"></i></span>@endif
                        <div class="min-w-0"><div class="fw-semibold text-truncate">{{ $p->trans('name') }}</div><div class="small text-muted figure">{{ $p->sku ?: '—' }}@if ($p->isVariable()) · {{ trans_choice(':n variant|:n variants', $p->variants->count(), ['n' => $p->variants->count()]) }}@endif</div></div>
                    </div>
