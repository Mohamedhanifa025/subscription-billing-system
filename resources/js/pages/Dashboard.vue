<template>
  <main class="shell">
    <header>
      <strong>Merchant Dashboard — {{ merchantName }}</strong>
    </header>

    <section class="stats">
      <article class="stat blue">
        <label>Current Cycle Usage</label>
        <b>{{ fmt(usage) }} / {{ fmt(allowance) }} units</b>
      </article>
      <article class="stat orange">
        <label>Projected Overage Revenue</label>
        <b>₹ {{ money(projected) }}</b>
      </article>
      <article class="stat green">
        <label>Active Plan</label>
        <b>{{ planName }}</b>
      </article>
    </section>

    <section class="content">
      <div class="left">
        <h3>Top 5 Customers by Usage (this cycle)</h3>
        <table>
          <thead>
            <tr>
              <th>Customer</th>
              <th>Usage</th>
              <th>% of Allowance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in topCustomers" :key="c.id">
              <td>{{ c.name }}</td>
              <td>{{ fmt(c.usage) }}</td>
              <td>{{ Math.round((c.usage / allowance) * 100) }}%</td>
            </tr>
          </tbody>
        </table><p class="hint">… (top 5, sorted desc.)</p>

        <h3 class="trend-title">Daily Usage Trend (last 30 days)</h3>
        <div class="chart">
          <span>usage / day</span>
          <svg viewBox="0 0 500 155" preserveAspectRatio="none">
            <polyline :points="chartPoints" />
          </svg>
        </div>
      </div>

      <aside>
        <section class="risk">
          <h3>⚠ Churn Risk (usage ↓ &gt;50% MoM)</h3>
          <p v-for="c in churnRisk" :key="c.id">
            • {{ c.name }} — {{ drop(c) }}% drop
          </p>
        </section>
        <section class="status">
          <h3>System status (informational)</h3>
          <p>Plan pricing cache: Redis, TTL 10m</p>
          <p>Nightly aggregation job: queued, chunked (5k rows/batch)</p>
          <p>Usage endpoint: rate-limited</p>
          <p>120 req/min per API key</p>
        </section>
      </aside>
    </section>
  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
const merchantId = 1;
const merchantName = 'Acme Corp';

const usage = ref(184320);
const allowance = ref(250000);
const projected = ref(42600);
const planName = ref('Growth — monthly');

const topCustomers = ref([
  { id: 1, name: 'Beta Retail Pvt Ltd', usage: 38200 },
  { id: 2, name: 'Craft Foods Co.', usage: 31050 },
  { id: 3, name: 'Delta Mart', usage: 27900 },
]);

const churnRisk = ref([
  { id: 1, name: 'Nova Traders', previous_usage: 100, current_usage: 38 },
  { id: 2, name: 'QuickMart', previous_usage: 100, current_usage: 45 },
]);

const trend = ref([
  40, 36, 39, 32, 25, 31, 27, 29, 22, 28,
  26, 20, 20, 24, 28, 22, 20, 20, 27, 31,
  25, 32, 27, 25, 20, 28, 32, 26, 24, 22,
]);

const chartPoints = computed(() =>
  trend.value
    .map((value, index) =>
      `${(index * 500) / (trend.value.length - 1)},${150 - value * 3}`,
    )
    .join(' '),
);

const fmt = (value) => Number(value).toLocaleString('en-IN');
const money = (value) => Number(value).toLocaleString('en-IN');
const drop = (customer) =>
  Math.round((1 - customer.current_usage / customer.previous_usage) * 100);

onMounted(async () => {
  try {
    const response = await fetch(`/api/merchants/${merchantId}/dashboard`);

    if (!response.ok) return;

    const data = await response.json();
    topCustomers.value = data.top_customers;
    churnRisk.value = data.churn_risk;
    projected.value = data.projected_overage_revenue_cents / 100;
    trend.value = data.daily_trend.map((item) => item.quantity);
  } catch (error) {
    // Demo fallback data remains visible.
  }
});
</script>
