@props([
    // What the filter is called: the label over its controls.
    'title',
])

{{-- A filter is a label, the controls it names, and the rule that separates it
     from the next filter. The box this used to draw is gone, so the sidebar
     reads as a list of filters rather than a stack of boxes.

     The rule is the shared separator, which owns the room on either side of
     itself: every gap in the sidebar is that same space, so the filters and the
     header's own line all sit the same distance apart.

     The group stays a group to a reader, which is what the fieldset gave it:
     without that, the controls would be announced one by one with nothing
     saying which filter they belong to. --}}
<div class="filter-group" role="group" aria-label="{{ $title }}" {{ $attributes }}>
    <p class="filter-group__title">{{ $title }}</p>

    <div class="filter-group__options">{{ $slot }}</div>
</div>

<hr class="separator separator--horizontal filter-separator" />
