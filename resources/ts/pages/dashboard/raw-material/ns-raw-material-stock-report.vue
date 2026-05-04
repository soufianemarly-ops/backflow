<template>
    <div id="raw-material-stock-report" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="loadReport()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Charger' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="printReport()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Imprimer' ) }}</span>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 anim-duration-500 fade-in-entrance">
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Total matières' ) }}</div>
                <div class="text-2xl font-bold">{{ stats.total }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'En alerte' ) }}</div>
                <div class="text-2xl font-bold" :class="stats.alerts > 0 ? 'text-error-primary' : ''">{{ stats.alerts }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Valeur totale du stock' ) }}</div>
                <div class="text-2xl font-bold">{{ nsCurrency( stats.totalValue ) }}</div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance" id="report-print-area">
            <div class="ns-box-body">
                <div class="flex justify-between items-center p-3 border-b">
                    <div class="text-sm text-fontcolor">
                        <ul>
                            <li>{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li>{{ __( 'Document : Rapport de stock matières premières' ) }}</li>
                            <li>{{ __( 'Par : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                </div>
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Matière première' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Unité' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Stock actuel' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Seuil alerte' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Coût/unité' ) }}</th>
                            <th width="150" class="border border-success-secondary bg-success-primary p-2 text-right">{{ __( 'Valeur stock' ) }}</th>
                            <th width="100" class="border p-2 text-center">{{ __( 'État' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="7" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="report.length === 0">
                            <td colspan="7" class="p-4 border text-center">
                                {{ __( 'Aucune donnée à afficher.' ) }}
                            </td>
                        </tr>
                        <tr v-for="item in report" :key="item.id" class="text-sm">
                            <td class="p-2 border font-medium">{{ item.name }}</td>
                            <td class="p-2 border">{{ item.unit }}</td>
                            <td class="p-2 border text-right">{{ item.stock_quantity }}</td>
                            <td class="p-2 border text-right">{{ item.alert_quantity }}</td>
                            <td class="p-2 border text-right">{{ nsCurrency( item.cost_per_unit ) }}</td>
                            <td class="p-2 border border-success-secondary bg-success-primary text-right">
                                {{ nsCurrency( item.stock_quantity * item.cost_per_unit ) }}
                            </td>
                            <td class="p-2 border text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs font-semibold"
                                    :class="isLowStock( item ) ? 'bg-error-primary text-white' : 'bg-success-primary'"
                                >
                                    {{ isLowStock( item ) ? __( 'Alerte' ) : __( 'OK' ) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="report.length > 0">
                        <tr class="font-semibold">
                            <td colspan="5" class="p-2 border text-right">{{ __( 'Total' ) }}</td>
                            <td class="p-2 border border-success-secondary bg-success-primary text-right">
                                {{ nsCurrency( totalStockValue ) }}
                            </td>
                            <td class="p-2 border"></td>
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
    name: 'ns-raw-material-stock-report',
    components: { nsSpinner },
    data() {
        return {
            ns: window.ns,
            report: [],
            isLoading: false,
        };
    },
    computed: {
        stats() {
            return {
                total: this.report.length,
                alerts: this.report.filter( i => this.isLowStock( i ) ).length,
                totalValue: this.totalStockValue,
            };
        },
        totalStockValue() {
            return this.report.reduce( ( sum, i ) => sum + ( i.stock_quantity * i.cost_per_unit ), 0 );
        },
    },
    mounted() {
        this.loadReport();
    },
    methods: {
        __,
        nsCurrency,
        isLowStock( item ) {
            return item.alert_quantity > 0 && item.stock_quantity <= item.alert_quantity;
        },
        loadReport() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/stock-report' ).subscribe({
                next: ( data ) => { this.report = data.data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger le rapport.' ) ); this.isLoading = false; },
            });
        },
        printReport() {
            this.$htmlToPaper( 'report-print-area' );
        },
    },
};
</script>
