@extends('world.layout')

@section('title')
    Universal Trait Index
@endsection

@section('content')
    {!! breadcrumbs(['World' => 'world', 'Universal Trait Index' => 'world/universaltraits']) !!}
    <h1>Universal Trait Index</h1>

    <p>This is a visual index of all universal traits. Click a trait to view more info on it!</p>

    @foreach ($features as $categoryId => $categoryFeatures)
        @if (!isset($categories[$categoryId]) || (Auth::check() && Auth::user()->hasPower('edit_data')) || $categories[$categoryId]->is_visible)
            <div class="card mb-3 inventory-category">
                <h5 class="card-header inventory-header">
                    @if (isset($categories[$categoryId]) && !$categories[$categoryId]->is_visible)
                        <i class="fas fa-eye-slash mr-1"></i>
                    @endif
                    {!! isset($categories[$categoryId]) ? '<a href="' . $categories[$categoryId]->searchUrl . '">' . $categories[$categoryId]->name . '</a>' : 'Miscellaneous' !!}
                </h5>
                <div class="card-body inventory-body">
                    @foreach ($categoryFeatures->chunk(4) as $chunk)
                        <div class="row mb-3">
                            @foreach ($chunk as $featureId => $feature)
                                <div class="col-sm-3 col-6 text-center align-self-center inventory-item">
                                    @if ($feature->first()->has_image)
                                        <a class="badge" style="border-radius:.5em; {{ $feature->first()->rarity->color ? 'background-color:#' . $feature->first()->rarity->color : '' }}" href="{{ $feature->first()->url }}">
                                            <img class="my-1 modal-image" style="max-height:100%; height:150px; border-radius:.5em;" src="{{ $feature->first()->imageUrl }}" alt="{{ $feature->first()->name }}" data-id="{{ $feature->first()->id }}" />
                                        </a>
                                    @endif
                                    <p>
                                        {!! $feature->first()->displayName !!}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('scripts')
    @if (config('lorekeeper.extensions.universal_trait_index.trait_modals'))
        <script>
            $(document).ready(function() {
                $('.modal-image').on('click', function(e) {
                    e.preventDefault();
                    loadModal("{{ url('world/universaltraits/trait') }}/" + $(this).data('id'), 'Trait Detail');
                });
            })
        </script>
    @endif
@endsection
