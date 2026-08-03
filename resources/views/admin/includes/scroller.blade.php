
<div>
    <?php $a = announcements('scroll'); ?>
    @if ($a?->status === 'active')
        <div class="p-1" style="background: #F2F4F4">
            <div class="marquee-container">
                <div class="marquee-content">{!! $a->message !!}</div>
            </div>
            <style>
                .marquee-container {
                    /* overflow: hidden; */
                    white-space: nowrap;
                }

                .marquee-content {
                    /* display: inline-block; */
                    animation: marqueeAnimation 15s linear infinite;
                }

                .marquee-content>* {
                    color: red;
                    display: inline-block;
                }

                @keyframes marqueeAnimation {
                    from {
                        transform: translateX(100%);
                    }

                    to {
                        transform: translateX(-100%);
                    }
                }

                .marquee-content:hover {
                    animation-play-state: paused;
                }
            </style>
        </div>
    @endif
</div>