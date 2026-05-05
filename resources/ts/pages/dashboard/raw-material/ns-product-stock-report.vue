<template>
    <div id="product-stock-report" class="px-4">
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
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Total produits' ) }}</div>
                <div class="text-2xl font-bold">{{ stats.total }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Produits en alerte' ) }}</div>
                <div class="text-2xl font-bold" :class="stats.alerts > 0 ? 'text-error-primary' : ''">{{ stats.alerts }}</div>
            </div>
            <div class="ns-box shadow rounded p-4">
                <div class="text-xs uppercase font-semibold mb-1 text-gray-500">{{ __( 'Valeur totale stock' ) }}</div>
                <div class="text-2xl font-bold">{{ nsCurrency( stats.totalValue ) }}</div>
            </div>
        </div>

        <!-- Stock Chart -->
        <div v-if="report.length > 0" class="ns-box shadow rounded p-4 mb-6 anim-duration-500 fade-in-entrance">
            <h3 class="text-sm font-semibold mb-3">{{ __( 'Stock actuel des produits finis' ) }}</h3>
            <div style="height: 260px; position: relative;">
                <Bar :data="stockBarData" :options="stockBarOptions" />
            </div>
        </div>

        <!-- Stock Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance" id="product-report-print-area">
            <div class="ns-box-body">
                <div class="flex justify-between items-center p-3 border-b">
                    <div class="text-sm text-fontcolor">
                        <ul>
                            <li>{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li>{{ __( 'Document : Rapport de stock produits finis' ) }}</li>
                            <li>{{ __( 'Par : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                </div>
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Produit' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Catégorie' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Unité' ) }}</th>
                            <th width="110" class="border p-2 text-right">{{ __( 'Stock actuel' ) }}</th>
                            <th width="110" class="border p-2 text-right">{{ __( 'Seuil alerte' ) }}</th>
                            <th width="120" class="border p-2 text-right">{{ __( 'Prix de vente' ) }}</th>
                            <th width="150" class="border border-success-secondary bg-success-primary p-2 text-right">{{ __( 'Valeur stock' ) }}</th>
                            <th width="90" class="border p-2 text-center">{{ __( 'État' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="8" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="report.length === 0">
                            <td colspan="8" class="p-4 border text-center">
                                {{ __( 'Aucune donnée à afficher.' ) }}
                            </td>
                        </tr>
                        <tr
                            v-for="item in report"
                            :key="item.unit_quantity_id"
                            class="text-sm"
                            :class="isAlert( item ) ? 'bg-error-primary bg-opacity-10' : ''"
                        >
                            <td class="p-2 border font-medium" :class="isAlert( item ) ? 'text-error-primary' : ''">
                                {{ item.name }}
                            </td>
                            <td class="p-2 border text-gray-500">{{ item.category }}</td>
                            <td class="p-2 border">{{ item.unit }}</td>
                            <td class="p-2 border text-right font-semibold" :class="isAlert( item ) ? 'text-error-primary' : ''">
                                {{ item.quantity }}
                            </td>
                            <td class="p-2 border text-right">{{ item.low_quantity }}</td>
                            <td class="p-2 border text-right">{{ nsCurrency( item.sale_price ) }}</td>
                            <td class="p-2 border border-success-secondary bg-success-primary text-right">
                                {{ nsCurrency( item.total_value ) }}
                            </td>
                            <td class="p-2 border text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs font-semibold"
                                    :class="isAlert( item ) ? 'bg-error-primary text-white' : 'bg-success-primary'"
                                >
                                    {{ isAlert( item ) ? __( 'Alerte' ) : __( 'OK' ) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="report.length > 0">
                        <tr class="font-semibold">
                            <td colspan="6" class="p-2 border text-right">{{ __( 'Total' ) }}</td>
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
import { Chart, registerables } from 'chart.js';
import { Bar } from 'vue-chartjs';

Chart.register( ...registerables );

export default {
    name: 'ns-product-stock-report',
    components: { nsSpinner, Bar },
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
                total:      this.report.length,
                alerts:     this.report.filter( i => this.isAlert( i ) ).length,
                totalValue: this.totalStockValue,
            };
        },
        totalStockValue() {
            return this.report.reduce( ( sum, i ) => sum + i.total_value, 0 );
        },
        stockBarData() {
            const label = ( name ) => name.length > 18 ? name.substring( 0, 18 ) + '…' : name;
            return {
                labels: this.report.map( i => label( i.name ) ),
                datasets: [
                    {
                        label: this.__( 'Stock actuel' ),
                        data: this.report.map( i => i.quantity ),
                        backgroundColor: this.report.map( i =>
                            this.isAlert( i ) ? 'rgba(239,68,68,0.75)' : 'rgba(34,197,94,0.75)'
                        ),
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: this.__( 'Seuil alerte' ),
                        data: this.report.map( i => i.low_quantity ),
                        borderColor: 'rgba(239,68,68,0.85)',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [ 6, 4 ],
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(239,68,68,0.85)',
                        tension: 0,
                        order: 1,
                    },
                ],
            };
        },
        stockBarOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } },
            };
        },
    },
    mounted() {
        this.loadReport();
    },
    methods: {
        __,
        nsCurrency,
        isAlert( item ) {
            return item.low_quantity > 0 && item.quantity < item.low_quantity;
        },
        loadReport() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/products-stock-report' ).subscribe({
                next: ( data ) => { this.report = data.data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger le rapport.' ) ); this.isLoading = false; },
            });
        },
        printReport() {
            this.$htmlToPaper( 'product-report-print-area' );
        },
    },
};
</script>
