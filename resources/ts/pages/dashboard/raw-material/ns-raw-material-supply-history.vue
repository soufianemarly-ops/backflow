<template>
    <div id="supply-history" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="loadHistory()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Actualiser' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="printHistory()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Imprimer' ) }}</span>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 anim-duration-500 fade-in-entrance">
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Total entrées' ) }}</div>
                <div class="text-2xl font-bold">{{ history.length }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Fournisseurs distincts' ) }}</div>
                <div class="text-2xl font-bold">{{ distinctSuppliers }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Coût total approvisionnements' ) }}</div>
                <div class="text-2xl font-bold">{{ nsCurrency( totalCost ) }}</div>
            </div>
        </div>

        <!-- History Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance" id="supply-history-print-area">
            <div class="ns-box-body">
                <div class="flex justify-between items-center p-3 border-b">
                    <div class="text-sm text-fontcolor">
                        <ul>
                            <li>{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li>{{ __( 'Document : Historique des approvisionnements' ) }}</li>
                            <li>{{ __( 'Par : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                </div>
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Date' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Matière première' ) }}</th>
                            <th width="100" class="border p-2 text-right">{{ __( 'Quantité' ) }}</th>
                            <th width="120" class="border p-2 text-right">{{ __( 'Coût/unité' ) }}</th>
                            <th width="130" class="border border-success-secondary bg-success-primary p-2 text-right">{{ __( 'Coût total' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Fournisseur' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Utilisateur' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Note' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="8" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="history.length === 0">
                            <td colspan="8" class="p-4 border text-center">
                                {{ __( 'Aucun approvisionnement enregistré.' ) }}
                            </td>
                        </tr>
                        <tr v-for="entry in history" :key="entry.id" class="text-sm">
                            <td class="p-2 border whitespace-nowrap">{{ formatDate( entry.created_at ) }}</td>
                            <td class="p-2 border font-medium">
                                {{ entry.material_name }}
                                <span class="text-xs opacity-60 ml-1">{{ entry.material_unit }}</span>
                            </td>
                            <td class="p-2 border text-right font-semibold">{{ entry.quantity }}</td>
                            <td class="p-2 border text-right">
                                {{ entry.cost_per_unit !== null ? nsCurrency( entry.cost_per_unit ) : '—' }}
                            </td>
                            <td class="p-2 border border-success-secondary bg-success-primary text-right">
                                {{ entry.total_cost !== null ? nsCurrency( entry.total_cost ) : '—' }}
                            </td>
                            <td class="p-2 border">{{ entry.supplier || '—' }}</td>
                            <td class="p-2 border text-gray-500">{{ entry.author }}</td>
                            <td class="p-2 border text-gray-400 text-xs">{{ entry.note || '' }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="history.length > 0">
                        <tr class="font-semibold">
                            <td colspan="4" class="p-2 border text-right">{{ __( 'Total' ) }}</td>
                            <td class="p-2 border border-success-secondary bg-success-primary text-right">
                                {{ nsCurrency( totalCost ) }}
                            </td>
                            <td colspan="3" class="p-2 border"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import nsSpinner from '~/components/ns-spinner.vue';

export default {
    name: 'ns-raw-material-supply-history',
    components: { nsSpinner },
    data() {
        return {
            ns: window.ns,
            history: [],
            isLoading: false,
        };
    },
    computed: {
        totalCost() {
            return this.history.reduce( ( sum, e ) => sum + ( e.total_cost || 0 ), 0 );
        },
        distinctSuppliers() {
            return new Set( this.history.map( e => e.supplier ).filter( Boolean ) ).size;
        },
    },
    mounted() {
        this.loadHistory();
    },
    methods: {
        __,
        nsCurrency,
        formatDate( dateStr ) {
            if ( ! dateStr ) return '—';
            return new Date( dateStr ).toLocaleString( 'fr-FR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            } );
        },
        loadHistory() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/supply-history' ).subscribe({
                next: ( data ) => { this.history = data.data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger l\'historique.' ) ); this.isLoading = false; },
            });
        },
        printHistory() {
            this.$htmlToPaper( 'supply-history-print-area' );
        },
    },
};
</script>
