<template>
    <div id="consumption-logs" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="loadLogs()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Actualiser' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="printLogs()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Imprimer' ) }}</span>
                </button>
            </div>

            <!-- Filter by material -->
            <div class="ns-select flex items-center shadow rounded">
                <select v-model="selectedMaterialId" class="rounded px-3 py-2 text-sm bg-transparent outline-none" style="min-width:200px;">
                    <option value="">{{ __( '— Toutes les matières —' ) }}</option>
                    <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </div>

            <div v-if="selectedMaterialId" class="ns-button">
                <button @click="selectedMaterialId = ''" class="rounded flex items-center shadow py-1 px-3 text-sm">
                    <i class="las la-times mr-1"></i>{{ __( 'Réinitialiser' ) }}
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 anim-duration-500 fade-in-entrance">
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Total déductions' ) }}</div>
                <div class="text-2xl font-bold">{{ filteredLogs.length }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">
                    {{ __( 'Quantité totale consommée' ) }}
                    <span v-if="selectedMaterialId" class="normal-case font-normal opacity-60"> ({{ currentUnit }})</span>
                </div>
                <div class="text-2xl font-bold">{{ totalQuantity }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Coût total estimé' ) }}</div>
                <div class="text-2xl font-bold">{{ nsCurrency( totalCost ) }}</div>
            </div>
        </div>

        <!-- Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance" id="consumption-logs-print-area">
            <div class="ns-box-body">
                <div class="flex justify-between items-center p-3 border-b">
                    <div class="text-sm text-fontcolor">
                        <ul>
                            <li>{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li>{{ __( 'Document : Logs de consommation matières premières' ) }}</li>
                            <li>{{ __( 'Par : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                            <li v-if="selectedMaterialId">
                                {{ __( 'Filtre : {name}' ).replace( '{name}', currentMaterialName ) }}
                            </li>
                        </ul>
                    </div>
                </div>
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Date' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Matière première' ) }}</th>
                            <th width="110" class="border p-2 text-right">{{ __( 'Quantité' ) }}</th>
                            <th width="70"  class="border p-2 text-center">{{ __( 'Unité' ) }}</th>
                            <th width="140" class="border border-error-secondary bg-error-primary p-2 text-right">{{ __( 'Coût estimé' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Commande liée' ) }}</th>
                            <th width="120" class="border p-2 text-left">{{ __( 'Utilisateur' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="7" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="filteredLogs.length === 0">
                            <td colspan="7" class="p-4 border text-center">
                                {{ __( 'Aucune déduction enregistrée.' ) }}
                            </td>
                        </tr>
                        <tr v-for="entry in filteredLogs" :key="entry.id" class="text-sm">
                            <td class="p-2 border whitespace-nowrap">{{ formatDate( entry.created_at ) }}</td>
                            <td class="p-2 border font-medium">{{ entry.material_name }}</td>
                            <td class="p-2 border text-right font-semibold">{{ entry.quantity }}</td>
                            <td class="p-2 border text-center text-gray-500">{{ entry.material_unit }}</td>
                            <td class="p-2 border border-error-secondary bg-error-primary text-right">
                                {{ nsCurrency( entry.cost_total ) }}
                            </td>
                            <td class="p-2 border text-xs">{{ entry.order_ref }}</td>
                            <td class="p-2 border text-gray-500">{{ entry.author }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="filteredLogs.length > 0">
                        <tr class="font-semibold">
                            <td colspan="4" class="p-2 border text-right">{{ __( 'Total' ) }}</td>
                            <td class="p-2 border border-error-secondary bg-error-primary text-right">
                                {{ nsCurrency( totalCost ) }}
                            </td>
                            <td colspan="2" class="p-2 border"></td>
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
    name: 'ns-raw-material-consumption-logs',
    components: { nsSpinner },
    data() {
        return {
            ns: window.ns,
            logs: [],
            isLoading: false,
            selectedMaterialId: '',
        };
    },
    computed: {
        materials() {
            const seen = new Set();
            return this.logs
                .filter( e => { if ( seen.has( e.material_id ) ) return false; seen.add( e.material_id ); return true; } )
                .map( e => ( { id: e.material_id, name: e.material_name } ) )
                .sort( ( a, b ) => a.name.localeCompare( b.name ) );
        },
        filteredLogs() {
            if ( ! this.selectedMaterialId ) return this.logs;
            return this.logs.filter( e => e.material_id === this.selectedMaterialId );
        },
        totalQuantity() {
            const sum = this.filteredLogs.reduce( ( s, e ) => s + e.quantity, 0 );
            return parseFloat( sum.toFixed( 3 ) );
        },
        totalCost() {
            return this.filteredLogs.reduce( ( s, e ) => s + e.cost_total, 0 );
        },
        currentUnit() {
            if ( ! this.selectedMaterialId ) return '';
            const entry = this.logs.find( e => e.material_id === this.selectedMaterialId );
            return entry?.material_unit ?? '';
        },
        currentMaterialName() {
            const m = this.materials.find( m => m.id === this.selectedMaterialId );
            return m?.name ?? '';
        },
    },
    mounted() {
        this.loadLogs();
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
        loadLogs() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/consumption-logs' ).subscribe({
                next: ( data ) => { this.logs = data.data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger les logs.' ) ); this.isLoading = false; },
            });
        },
        printLogs() {
            this.$htmlToPaper( 'consumption-logs-print-area' );
        },
    },
};
</script>
