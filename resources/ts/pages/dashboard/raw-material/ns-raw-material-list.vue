<template>
    <div id="raw-material-section" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="openCreateForm()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-plus text-xl"></i>
                    <span class="pl-2">{{ __( 'Ajouter' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="loadRawMaterials()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Actualiser' ) }}</span>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance">
            <div class="ns-box-body">
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Nom' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Unité' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Stock' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Seuil alerte' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Coût/unité' ) }}</th>
                            <th width="160" class="border p-2 text-center">{{ __( 'Actions' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="6" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="rawMaterials.length === 0">
                            <td colspan="6" class="p-4 border text-center">
                                {{ __( 'Aucune matière première trouvée.' ) }}
                            </td>
                        </tr>
                        <tr
                            v-for="material in rawMaterials"
                            :key="material.id"
                            class="text-sm"
                            :class="{ 'border-error-secondary bg-error-primary': isLowStock( material ) }"
                        >
                            <td class="p-2 border font-semibold">{{ material.name }}</td>
                            <td class="p-2 border">{{ material.unit }}</td>
                            <td class="p-2 border text-right">{{ material.stock_quantity }}</td>
                            <td class="p-2 border text-right">{{ material.alert_quantity }}</td>
                            <td class="p-2 border text-right">{{ nsCurrency( material.cost_per_unit ) }}</td>
                            <td class="p-2 border text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="openSupplyForm( material )" class="ns-button success rounded px-2 py-1 text-xs" :title="__( 'Approvisionner' )">
                                        <i class="las la-dolly text-sm"></i>
                                    </button>
                                    <button @click="openEditForm( material )" class="ns-button rounded px-2 py-1 text-xs" :title="__( 'Modifier' )">
                                        <i class="las la-edit"></i>
                                    </button>
                                    <button @click="deleteMaterial( material )" class="ns-button error rounded px-2 py-1 text-xs" :title="__( 'Supprimer' )">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.5);">
            <div class="ns-box shadow-lg rounded w-full max-w-lg mx-4">
                <div class="ns-box-header flex justify-between items-center p-4 border-b">
                    <h3 class="font-semibold text-lg">{{ editingMaterial ? __( 'Modifier la matière' ) : __( 'Nouvelle matière première' ) }}</h3>
                    <button @click="closeForm()" class="ns-button rounded px-2 py-1">
                        <i class="las la-times text-xl"></i>
                    </button>
                </div>
                <div class="ns-box-body p-4 flex flex-col gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Nom' ) }} *</label>
                        <input v-model="form.name" type="text" class="ns-input w-full rounded border px-3 py-2 text-sm" :placeholder="__( 'ex: Farine de blé' )">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Unité' ) }} *</label>
                        <select v-model="form.unit" class="ns-select w-full rounded border px-3 py-2 text-sm">
                            <option value="g">g (gramme)</option>
                            <option value="kg">kg (kilogramme)</option>
                            <option value="L">L (litre)</option>
                            <option value="ml">ml (millilitre)</option>
                            <option value="pcs">pcs (pièce)</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Stock initial' ) }}</label>
                            <input v-model.number="form.stock_quantity" type="number" min="0" step="0.01" class="ns-input w-full rounded border px-3 py-2 text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Seuil d\'alerte' ) }}</label>
                            <input v-model.number="form.alert_quantity" type="number" min="0" step="0.01" class="ns-input w-full rounded border px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Coût par unité' ) }}</label>
                        <input v-model.number="form.cost_per_unit" type="number" min="0" step="0.01" class="ns-input w-full rounded border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Description' ) }}</label>
                        <textarea v-model="form.description" rows="2" class="ns-input w-full rounded border px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
                <div class="ns-box-footer flex justify-end gap-2 p-4 border-t">
                    <button @click="closeForm()" class="ns-button rounded px-4 py-2">{{ __( 'Annuler' ) }}</button>
                    <button @click="saveMaterial()" :disabled="isSaving" class="ns-button info rounded px-4 py-2">
                        <ns-spinner v-if="isSaving" size="4"></ns-spinner>
                        <span v-else>{{ __( 'Enregistrer' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Supply Modal -->
        <div v-if="showSupplyForm" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.5);">
            <div class="ns-box shadow-lg rounded w-full max-w-md mx-4">
                <div class="ns-box-header flex justify-between items-center p-4 border-b">
                    <div>
                        <h3 class="font-semibold text-lg">{{ __( 'Approvisionner' ) }}</h3>
                        <p class="text-sm opacity-60">{{ supplyingMaterial?.name }}</p>
                    </div>
                    <button @click="closeSupplyForm()" class="ns-button rounded px-2 py-1">
                        <i class="las la-times text-xl"></i>
                    </button>
                </div>
                <div class="ns-box-body p-4 flex flex-col gap-3">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Quantité reçue' ) }} *</label>
                            <input v-model.number="supplyForm.quantity" type="number" min="0.001" step="0.001" class="ns-input w-full rounded border px-3 py-2 text-sm" autofocus>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">
                                {{ __( 'Coût unitaire' ) }}
                                <span class="opacity-50 font-normal">({{ supplyingMaterial?.unit }})</span>
                            </label>
                            <input v-model.number="supplyForm.cost_per_unit" type="number" min="0" step="0.01" class="ns-input w-full rounded border px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Fournisseur' ) }}</label>
                        <input v-model="supplyForm.supplier" type="text" class="ns-input w-full rounded border px-3 py-2 text-sm" :placeholder="__( 'ex: Dupont & Fils' )">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Note' ) }}</label>
                        <input v-model="supplyForm.note" type="text" class="ns-input w-full rounded border px-3 py-2 text-sm" :placeholder="__( 'ex: Livraison BL-2026-001' )">
                    </div>
                    <div class="ns-box rounded p-3 text-sm opacity-70">
                        {{ __( 'Stock actuel : {qty} {unit}' )
                            .replace( '{qty}', supplyingMaterial?.stock_quantity ?? 0 )
                            .replace( '{unit}', supplyingMaterial?.unit ?? '' ) }}
                        → {{ __( 'Après : {qty} {unit}' )
                            .replace( '{qty}', ((supplyingMaterial?.stock_quantity ?? 0) + (supplyForm.quantity || 0)).toFixed(3).replace(/\.?0+$/, '') )
                            .replace( '{unit}', supplyingMaterial?.unit ?? '' ) }}
                    </div>
                </div>
                <div class="ns-box-footer flex justify-end gap-2 p-4 border-t">
                    <button @click="closeSupplyForm()" class="ns-button rounded px-4 py-2">{{ __( 'Annuler' ) }}</button>
                    <button @click="submitSupply()" :disabled="isSupplying" class="ns-button info rounded px-4 py-2">
                        <ns-spinner v-if="isSupplying" size="4"></ns-spinner>
                        <span v-else><i class="las la-check mr-1"></i>{{ __( 'Confirmer' ) }}</span>
                    </button>
                </div>
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
    name: 'ns-raw-material-list',
    components: { nsSpinner },
    data() {
        return {
            rawMaterials: [],
            isLoading: false,
            isSaving: false,
            showForm: false,
            editingMaterial: null,
            form: this.emptyForm(),
            showSupplyForm: false,
            supplyingMaterial: null,
            isSupplying: false,
            supplyForm: this.emptySupplyForm(),
        };
    },
    mounted() {
        this.loadRawMaterials();
    },
    methods: {
        __,
        nsCurrency,
        emptyForm() {
            return { name: '', unit: 'kg', stock_quantity: 0, alert_quantity: 0, cost_per_unit: 0, description: '' };
        },
        emptySupplyForm() {
            return { quantity: 1, cost_per_unit: 0, supplier: '', note: '' };
        },
        isLowStock( material ) {
            return material.alert_quantity > 0 && material.stock_quantity <= material.alert_quantity;
        },
        loadRawMaterials() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/raw-materials' ).subscribe({
                next: ( data ) => {
                    this.rawMaterials = data;
                    this.isLoading = false;
                },
                error: () => {
                    nsSnackBar.error( __( 'Impossible de charger les matières premières.' ) );
                    this.isLoading = false;
                },
            });
        },
        openCreateForm() {
            this.editingMaterial = null;
            this.form = this.emptyForm();
            this.showForm = true;
        },
        openEditForm( material ) {
            this.editingMaterial = material;
            this.form = { ...material };
            this.showForm = true;
        },
        closeForm() {
            this.showForm = false;
        },
        saveMaterial() {
            if ( ! this.form.name ) {
                nsSnackBar.error( __( 'Le nom est obligatoire.' ) );
                return;
            }
            this.isSaving = true;
            const request = this.editingMaterial
                ? nsHttpClient.put( `/api/ns-raw-material/raw-materials/${this.editingMaterial.id}`, this.form )
                : nsHttpClient.post( '/api/ns-raw-material/raw-materials', this.form );

            request.subscribe({
                next: () => {
                    nsSnackBar.success( __( 'Matière première enregistrée.' ) );
                    this.isSaving = false;
                    this.closeForm();
                    this.loadRawMaterials();
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message || __( 'Une erreur est survenue.' ) );
                    this.isSaving = false;
                },
            });
        },
        openSupplyForm( material ) {
            this.supplyingMaterial = material;
            this.supplyForm = { ...this.emptySupplyForm(), cost_per_unit: material.cost_per_unit ?? 0 };
            this.showSupplyForm = true;
        },
        closeSupplyForm() {
            this.showSupplyForm = false;
            this.supplyingMaterial = null;
        },
        submitSupply() {
            if ( ! this.supplyForm.quantity || this.supplyForm.quantity <= 0 ) {
                nsSnackBar.error( __( 'La quantité doit être supérieure à 0.' ) );
                return;
            }
            this.isSupplying = true;
            nsHttpClient.post(
                `/api/ns-raw-material/raw-materials/${this.supplyingMaterial.id}/add-stock`,
                this.supplyForm
            ).subscribe({
                next: () => {
                    nsSnackBar.success( __( 'Approvisionnement enregistré.' ) );
                    this.isSupplying = false;
                    this.closeSupplyForm();
                    this.loadRawMaterials();
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message || __( 'Une erreur est survenue.' ) );
                    this.isSupplying = false;
                },
            });
        },
        deleteMaterial( material ) {
            if ( ! confirm( __( 'Supprimer cette matière première ?' ) ) ) return;
            nsHttpClient.delete( `/api/ns-raw-material/raw-materials/${material.id}` ).subscribe({
                next: () => {
                    nsSnackBar.success( __( 'Matière supprimée.' ) );
                    this.loadRawMaterials();
                },
                error: () => {
                    nsSnackBar.error( __( 'Impossible de supprimer la matière.' ) );
                },
            });
        },
    },
};
</script>
