import './bootstrap';
import { createApp, ref, onMounted, computed } from 'vue';

const app = createApp({
    // Inline template to ensure visitors see content immediately
    template: `
        <h1 class="mb-4">Council Finance Counters</h1>
        <div class="mb-5">
            <h2 class="h4">Total Debt Across All Councils</h2>
            <div class="display-5 fw-bold">@{{ formattedDebt }}</div>
        </div>
        <div class="mb-3">
            <input type="text" class="form-control" placeholder="Search for a council" v-model="searchQuery" @input="searchCouncils"/>
            <ul class="list-group mt-2" v-if="councils.length">
                <li class="list-group-item" v-for="council in councils" :key="council.id">
                    <a :href="'/council/' + council.slug">@{{ council.name }}</a>
                </li>
            </ul>
        </div>
        <a href="/admin" class="btn btn-primary mt-4">Admin Login</a>
    `,
    setup() {
        const totalDebt = ref(0);
        const displayedDebt = ref(0);
        const searchQuery = ref('');
        const councils = ref([]);

        const fetchTotals = async () => {
            try {
                const response = await axios.get('/api/totals');
                totalDebt.value = response.data.totalDebt || 0;
                animateDebt();
            } catch (e) {
                console.error(e);
            }
        };

        const animateDebt = () => {
            const step = Math.ceil(totalDebt.value / 100);
            const interval = setInterval(() => {
                if (displayedDebt.value >= totalDebt.value) {
                    displayedDebt.value = totalDebt.value;
                    clearInterval(interval);
                } else {
                    displayedDebt.value += step;
                }
            }, 20);
        };

        const searchCouncils = async () => {
            if (!searchQuery.value) {
                councils.value = [];
                return;
            }
            try {
                const response = await axios.get('/api/councils/search', {
                    params: { q: searchQuery.value },
                });
                councils.value = response.data;
            } catch (e) {
                console.error(e);
            }
        };

        onMounted(fetchTotals);

        const formattedDebt = computed(() => {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency',
                currency: 'GBP',
                maximumFractionDigits: 0,
            }).format(displayedDebt.value);
        });

        return {
            totalDebt,
            displayedDebt,
            searchQuery,
            councils,
            searchCouncils,
            formattedDebt,
        };
    },
});

app.mount('#app');
