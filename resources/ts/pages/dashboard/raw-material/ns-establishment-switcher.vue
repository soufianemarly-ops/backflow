<template>
    <div class="relative px-2" v-if="establishments.length > 0">
        <!-- Trigger button -->
        <div class="ns-button" @click.stop="isOpen = !isOpen">
            <button class="rounded flex items-center shadow py-1 px-3 gap-2">
                <i :class="activeIcon" class="las text-xl"></i>
                <span class="text-sm font-semibold hidden md:inline">{{ active?.name || '…' }}</span>
                <i class="las la-angle-down text-xs transition-transform" :class="{ 'rotate-180': isOpen }"></i>
            </button>
        </div>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            @click.stop
            class="absolute right-0 mt-1 w-52 ns-box shadow-xl rounded-lg overflow-hidden z-50"
        >
            <div class="px-3 py-2 text-xs uppercase font-semibold opacity-60 border-b">
                {{ __( 'Choisir l\'établissement' ) }}
            </div>
            <ul>
                <li
                    v-for="e in establishments"
                    :key="e.id"
                    @click="switchTo( e.id )"
                    class="flex items-center gap-3 px-4 py-3 cursor-pointer text-sm hover:opacity-80 transition-opacity"
                    :class="e.id === active?.id ? 'font-bold' : ''"
                >
                    <i class="las text-xl" :class="e.type === 'production' ? 'la-industry' : 'la-store'"></i>
                    <div>
                        <div>{{ e.name }}</div>
                        <div class="text-xs opacity-50 capitalize">{{ e.type }}</div>
                    </div>
                    <i v-if="e.id === active?.id" class="las la-check ml-auto text-success-primary text-base"></i>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-establishment-switcher',

    data() {
        return {
            active:         null,
            establishments: [],
            isOpen:         false,
            isSwitching:    false,
        };
    },

    computed: {
        activeIcon() {
            if ( ! this.active ) return 'la-store';
            return this.active.type === 'production' ? 'la-industry' : 'la-store';
        },
    },

    mounted() {
        this.loadCurrent();
        document.addEventListener( 'click', this.closeDropdown );
    },

    beforeUnmount() {
        document.removeEventListener( 'click', this.closeDropdown );
    },

    methods: {
        __,

        closeDropdown() {
            this.isOpen = false;
        },

        loadCurrent() {
            nsHttpClient.get( '/api/ns-raw-material/establishment/current' ).subscribe( {
                next: ( data ) => {
                    this.active         = data.active;
                    this.establishments = data.establishments;
                },
                error: () => nsSnackBar.error( __( 'Impossible de charger les établissements.' ) ),
            } );
        },

        switchTo( id ) {
            if ( this.isSwitching || id === this.active?.id ) {
                this.isOpen = false;
                return;
            }

            this.isSwitching = true;

            nsHttpClient.post( '/api/ns-raw-material/establishment/switch', { establishment_id: id } ).subscribe( {
                next: () => {
                    window.location.reload();
                },
                error: () => {
                    nsSnackBar.error( __( 'Impossible de changer d\'établissement.' ) );
                    this.isSwitching = false;
                },
            } );
        },
    },
};
</script>
