<span class="badge rounded-pill text-bg-{{ ['active' => 'success', 'draft' => 'secondary', 'archived' => 'dark'][$p->status] }}">{{ __(ucfirst($p->status)) }}</span>
