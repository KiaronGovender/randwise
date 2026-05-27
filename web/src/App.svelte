<script lang="ts">
  import { onMount } from 'svelte'
  import {
    BadgeCheck,
    Bell,
    CalendarDays,
    ChartNoAxesColumnIncreasing,
    CircleDollarSign,
    EyeOff,
    ReceiptText,
    RefreshCw,
    Search,
    ShieldCheck,
    Sparkles,
    Target,
    TrendingUp,
    Upload,
    Wallet,
    WalletCards,
  } from '@lucide/svelte'

  type Tone = 'positive' | 'warning' | 'danger' | 'neutral'

  type Metric = {
    label: string
    value: number
    format: 'currency' | 'percent'
    tone: Tone
    detail: string
  }

  type Category = {
    name: string
    amount: number
    percentage: number
    color: string
    transactions: number
  }

  type Trend = {
    month: string
    income: number
    spending: number
  }

  type RecurringPayment = {
    merchant: string
    category: string
    monthlyCost: number
    yearlyCost: number
    lastSeen: string
    paymentsSeen: number
    status: 'review' | 'watch'
  }

  type Goal = {
    name: string
    target: number
    saved: number
    deadline: string
    recommendedMonthly: number
    status: string
  }

  type Insight = {
    title: string
    body: string
    tone: Tone
  }

  type Transaction = {
    date: string
    description: string
    merchant: string
    amount: number
    category: string
    type: 'income' | 'expense'
  }

  type DashboardData = {
    profile: {
      name: string
      bank: string
      period: string
      currency: string
      privacyMode: boolean
    }
    metrics: Metric[]
    healthScore: number
    cashLeftUntilPayday: number
    paydayCountdown: number
    categories: Category[]
    trends: Trend[]
    recurring: RecurringPayment[]
    goals: Goal[]
    insights: Insight[]
    transactions: Transaction[]
    meta: {
      transactionCount: number
      savingsOpportunity: number
      generatedAt: string
      importId?: number
      sourceFilename?: string
      persisted?: boolean
    }
  }

  type ImportSummary = {
    id: number
    bank: string
    sourceFilename: string
    importedAt: string
    transactionCount: number
    netCashFlow: number
    savingsRate: number
  }

  const banks = ['Capitec', 'FNB', 'Standard Bank', 'Absa', 'Nedbank', 'Other']

  const fallbackData: DashboardData = {
    profile: {
      name: 'RandWise demo profile',
      bank: 'Demo Capitec CSV',
      period: '01 Feb 2026 - 25 May 2026',
      currency: 'ZAR',
      privacyMode: true,
    },
    metrics: [
      {
        label: 'Monthly income',
        value: 24500,
        format: 'currency',
        tone: 'positive',
        detail: 'Average across imported deposits',
      },
      {
        label: 'Monthly spending',
        value: 13248,
        format: 'currency',
        tone: 'warning',
        detail: 'Average card swipes, EFTs, debit orders',
      },
      {
        label: 'Net cash flow',
        value: 11252,
        format: 'currency',
        tone: 'positive',
        detail: 'Money left after expenses',
      },
      {
        label: 'Savings rate',
        value: 45.9,
        format: 'percent',
        tone: 'positive',
        detail: 'Target: 15% or better',
      },
    ],
    healthScore: 82,
    cashLeftUntilPayday: 11252,
    paydayCountdown: 9,
    categories: [
      { name: 'Rent', amount: 31200, percentage: 58.9, color: '#334155', transactions: 4 },
      { name: 'Groceries', amount: 5195, percentage: 9.8, color: '#2f855a', transactions: 4 },
      { name: 'Insurance', amount: 5400, percentage: 10.2, color: '#0891b2', transactions: 4 },
      { name: 'Transport/Fuel', amount: 3380, percentage: 6.4, color: '#2563eb', transactions: 4 },
      { name: 'Prepaid Electricity', amount: 2000, percentage: 3.8, color: '#d97706', transactions: 2 },
      { name: 'Takeaways', amount: 973, percentage: 1.8, color: '#ea580c', transactions: 4 },
      { name: 'Shopping', amount: 1579, percentage: 3, color: '#db2777', transactions: 2 },
    ],
    trends: [
      { month: 'Feb', income: 24500, spending: 13336 },
      { month: 'Mar', income: 24500, spending: 13239 },
      { month: 'Apr', income: 24500, spending: 12364 },
      { month: 'May', income: 24500, spending: 14052 },
    ],
    recurring: [
      {
        merchant: 'Rent Greenpoint Property',
        category: 'Rent',
        monthlyCost: 7800,
        yearlyCost: 93600,
        lastSeen: '2026-05-01',
        paymentsSeen: 4,
        status: 'review',
      },
      {
        merchant: 'Discovery Insurance',
        category: 'Insurance',
        monthlyCost: 1350,
        yearlyCost: 16200,
        lastSeen: '2026-05-18',
        paymentsSeen: 4,
        status: 'review',
      },
      {
        merchant: 'Vodacom Contract Debit',
        category: 'Airtime/Data',
        monthlyCost: 399,
        yearlyCost: 4788,
        lastSeen: '2026-05-06',
        paymentsSeen: 4,
        status: 'watch',
      },
      {
        merchant: 'Netflix Subscription',
        category: 'Entertainment',
        monthlyCost: 199,
        yearlyCost: 2388,
        lastSeen: '2026-05-08',
        paymentsSeen: 4,
        status: 'watch',
      },
    ],
    goals: [
      {
        name: 'Emergency fund',
        target: 18000,
        saved: 6200,
        deadline: '2026-12-15',
        recommendedMonthly: 1800,
        status: 'On track with one spending trim',
      },
      {
        name: 'New laptop',
        target: 14000,
        saved: 3500,
        deadline: '2026-10-31',
        recommendedMonthly: 1200,
        status: 'Needs a smaller takeaway budget',
      },
    ],
    insights: [
      {
        title: 'Your biggest money lane',
        body: 'Rent is your largest expense category at R31 200 this period.',
        tone: 'neutral',
      },
      {
        title: 'Debit order detective',
        body: 'Recurring payments add up to about R116 976 per year. Review the high-cost ones first.',
        tone: 'warning',
      },
      {
        title: 'Practical saving move',
        body: 'A 25% trim on flexible categories could free roughly R638 next month.',
        tone: 'positive',
      },
      {
        title: 'Cash-flow check',
        body: 'You ended positive by R11 252. Move part of that into a goal before it disappears into small swipes.',
        tone: 'positive',
      },
    ],
    transactions: [
      {
        date: '2026-05-25',
        description: 'Salary Deposit FinTech Studio',
        merchant: 'Salary Deposit Fintech',
        amount: 24500,
        category: 'Income',
        type: 'income',
      },
      {
        date: '2026-05-22',
        description: 'Standard Bank Monthly Fee',
        merchant: 'Standard Bank Monthly',
        amount: -72,
        category: 'Banking Fees',
        type: 'expense',
      },
      {
        date: '2026-05-20',
        description: 'Cotton On Shopping',
        merchant: 'Cotton On Shopping',
        amount: -680,
        category: 'Shopping',
        type: 'expense',
      },
      {
        date: '2026-05-18',
        description: 'Discovery Insurance',
        merchant: 'Discovery Insurance',
        amount: -1350,
        category: 'Insurance',
        type: 'expense',
      },
      {
        date: '2026-05-14',
        description: 'Prepaid Electricity City Power',
        merchant: 'Prepaid Electricity City',
        amount: -1050,
        category: 'Prepaid Electricity',
        type: 'expense',
      },
      {
        date: '2026-05-12',
        description: 'Nandos Takeaway',
        merchant: 'Nandos Takeaway',
        amount: -228,
        category: 'Takeaways',
        type: 'expense',
      },
    ],
    meta: {
      transactionCount: 42,
      savingsOpportunity: 638,
      generatedAt: '2026-05-27T00:00:00+02:00',
      persisted: false,
    },
  }

  let dashboard = fallbackData
  let activeView: 'overview' | 'transactions' | 'privacy' = 'overview'
  let selectedBank = banks[0]
  let transactionSearch = ''
  let apiStatus = 'Sample data active'
  let savedImports: ImportSummary[] = []
  let importsStatus = 'No saved imports yet'
  let isLoading = false
  let uploadError = ''

  $: topCategories = dashboard.categories.slice(0, 7)
  $: filteredTransactions = dashboard.transactions.filter((transaction) => {
    const query = transactionSearch.trim().toLowerCase()

    if (!query) {
      return true
    }

    return [transaction.description, transaction.merchant, transaction.category]
      .join(' ')
      .toLowerCase()
      .includes(query)
  })

  onMount(() => {
    void loadDemo()
    void loadImports()
  })

  async function loadDemo() {
    isLoading = true
    uploadError = ''

    try {
      const response = await fetch('/api/demo')

      if (!response.ok) {
        throw new Error('Demo API unavailable')
      }

      dashboard = await response.json()
      apiStatus = 'Live API demo data'
    } catch {
      dashboard = fallbackData
      apiStatus = 'Sample data active'
    } finally {
      isLoading = false
    }
  }

  async function loadImports() {
    try {
      const response = await fetch('/api/imports')

      if (!response.ok) {
        throw new Error('Import history unavailable')
      }

      const payload = await response.json()
      savedImports = payload.data ?? []
      importsStatus = savedImports.length > 0 ? `${savedImports.length} saved` : 'No saved imports yet'
    } catch {
      savedImports = []
      importsStatus = 'Database unavailable'
    }
  }

  async function loadSavedImport(importId: number) {
    isLoading = true
    uploadError = ''

    try {
      const response = await fetch(`/api/imports/${importId}`)

      if (!response.ok) {
        throw new Error('Could not load saved import')
      }

      dashboard = await response.json()
      activeView = 'overview'
      apiStatus = 'Saved import loaded'
    } catch {
      uploadError = 'Could not load that saved import from the database.'
    } finally {
      isLoading = false
    }
  }

  async function handleStatementChange(event: Event) {
    const input = event.currentTarget as HTMLInputElement
    const file = input.files?.[0]

    if (!file) {
      return
    }

    isLoading = true
    uploadError = ''
    apiStatus = `Analysing ${file.name}`

    try {
      const statementContents = await file.text()
      const response = await fetch('/api/imports', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          bank: selectedBank,
          sourceFilename: file.name,
          statementContents,
        }),
      })

      if (!response.ok) {
        throw new Error('Statement import failed')
      }

      dashboard = await response.json()
      activeView = 'overview'
      apiStatus = `${file.name} saved`
      await loadImports()
    } catch {
      uploadError = 'Could not save the statement. Check your Laravel database connection and migrations.'
      dashboard = fallbackData
      apiStatus = 'Sample data active'
    } finally {
      isLoading = false
      input.value = ''
    }
  }

  function formatMetric(metric: Metric) {
    return metric.format === 'percent' ? `${metric.value.toFixed(1)}%` : formatCurrency(metric.value)
  }

  function formatCurrency(value: number) {
    const sign = value < 0 ? '-' : ''

    return `${sign}R${new Intl.NumberFormat('en-ZA', {
      maximumFractionDigits: 0,
    }).format(Math.abs(value))}`
  }

  function goalProgress(goal: Goal) {
    return Math.min((goal.saved / goal.target) * 100, 100)
  }

  function maxTrendValue() {
    return Math.max(...dashboard.trends.flatMap((trend) => [trend.income, trend.spending]), 1)
  }

  function barHeight(value: number) {
    return `${Math.max((value / maxTrendValue()) * 100, 4)}%`
  }

  function categoryBarWidth(category: Category) {
    return `${Math.min(Math.max(category.percentage, 4), 100)}%`
  }

  function shortDate(value: string) {
    return new Intl.DateTimeFormat('en-ZA', {
      day: '2-digit',
      month: 'short',
    }).format(new Date(value))
  }

  function donutStyle(categories: Category[]) {
    let cursor = 0
    const stops = categories.map((category) => {
      const start = cursor
      cursor += category.percentage

      return `${category.color} ${start}% ${cursor}%`
    })

    if (cursor < 100) {
      stops.push(`#e5e7eb ${cursor}% 100%`)
    }

    return `background: conic-gradient(${stops.join(', ')});`
  }
</script>

<main class="app-shell">
  <aside class="sidebar" aria-label="RandWise navigation">
    <div class="brand">
      <div class="brand-mark">R</div>
      <div>
        <strong>RandWise</strong>
        <span>SA money coach</span>
      </div>
    </div>

    <nav class="nav-list">
      <button class:active={activeView === 'overview'} onclick={() => (activeView = 'overview')}>
        <ChartNoAxesColumnIncreasing size={18} />
        Overview
      </button>
      <button
        class:active={activeView === 'transactions'}
        onclick={() => (activeView = 'transactions')}
      >
        <ReceiptText size={18} />
        Transactions
      </button>
      <button class:active={activeView === 'privacy'} onclick={() => (activeView = 'privacy')}>
        <ShieldCheck size={18} />
        Privacy
      </button>
    </nav>

    <section class="import-history" aria-label="Saved statement imports">
      <div class="history-heading">
        <span>Saved imports</span>
        <small>{importsStatus}</small>
      </div>

      {#if savedImports.length > 0}
        <div class="history-list">
          {#each savedImports as importItem}
            <button
              class:current={dashboard.meta.importId === importItem.id}
              onclick={() => loadSavedImport(importItem.id)}
            >
              <strong>{importItem.bank}</strong>
              <span>{importItem.sourceFilename}</span>
              <small>
                {shortDate(importItem.importedAt)} / {importItem.transactionCount} txns
              </small>
            </button>
          {/each}
        </div>
      {:else}
        <p class="history-empty">Upload a CSV to create your first saved import.</p>
      {/if}
    </section>

    <div class="sidebar-note">
      <ShieldCheck size={18} />
      <span>POPIA-aware demo with masked statement data.</span>
    </div>
  </aside>

  <section class="workspace">
    <header class="topbar">
      <div class="title-stack">
        <p class="eyebrow">Personal finance coach for South Africans</p>
        <h1>{dashboard.profile.bank}</h1>
        <div class="title-meta">
          <span class="period">{dashboard.profile.period}</span>
          <span class="bank-badge">{dashboard.profile.currency}</span>
        </div>
      </div>

      <div class="topbar-actions">
        <select bind:value={selectedBank} aria-label="Select bank">
          {#each banks as bank}
            <option value={bank}>{bank}</option>
          {/each}
        </select>
        <button class="icon-button" title="Refresh demo data" onclick={loadDemo}>
          <RefreshCw size={18} class={isLoading ? 'spin' : ''} />
        </button>
        <button class="icon-button" title="Notifications">
          <Bell size={18} />
        </button>
        <label class="upload-button">
          <Upload size={18} />
          Upload CSV
          <input type="file" accept=".csv,text/csv" onchange={handleStatementChange} />
        </label>
      </div>
    </header>

    <section class="hero-band">
      <div class="health-score" aria-label={`Money health score ${dashboard.healthScore}`}>
        <span>Money health</span>
        <strong>{dashboard.healthScore}</strong>
        <small>/100</small>
      </div>
      <div>
        <p class="eyebrow">AI monthly summary</p>
        <h2>
          You have {formatCurrency(dashboard.cashLeftUntilPayday)} projected cash left with
          {dashboard.paydayCountdown} days to payday.
        </h2>
        <p>
          RandWise turns local bank statement data into cash-flow, debit order, and savings-goal
          decisions that feel practical in rand terms.
        </p>
        <div class="coach-chips" aria-label="Dashboard highlights">
          <span>Debit orders</span>
          <span>Local categories</span>
          <span>Goal planning</span>
        </div>
      </div>
      <div class="status-pill">
        <BadgeCheck size={17} />
        {apiStatus}
      </div>
    </section>

    {#if uploadError}
      <div class="alert" role="status">{uploadError}</div>
    {/if}

    {#if activeView === 'overview'}
      <section class="metrics-grid" aria-label="Financial metrics">
        {#each dashboard.metrics as metric}
          <article class={`metric-card ${metric.tone}`}>
            <div>
              <div class="metric-card-head">
                <span>{metric.label}</span>
                <i aria-hidden="true"></i>
              </div>
              <strong>{formatMetric(metric)}</strong>
            </div>
            <p>{metric.detail}</p>
          </article>
        {/each}
      </section>

      <section class="dashboard-grid">
        <article class="panel spending-panel">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Spending map</p>
              <h2>Where the rand went</h2>
            </div>
            <WalletCards size={22} />
          </div>

          <div class="spending-layout">
            <div class="donut" style={donutStyle(topCategories)}>
              <div>
                <strong>{formatCurrency(dashboard.meta.savingsOpportunity)}</strong>
                <span>flex trim</span>
              </div>
            </div>

            <div class="category-list">
              {#each topCategories as category}
                <div class="category-row">
                  <div class="category-meta">
                    <span class="swatch" style={`background: ${category.color};`}></span>
                    <span>{category.name}</span>
                    <small>{category.transactions} txns</small>
                  </div>
                  <strong>{formatCurrency(category.amount)}</strong>
                  <div class="bar-track" aria-hidden="true">
                    <span
                      style={`width: ${categoryBarWidth(category)}; background: ${category.color};`}
                    ></span>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        </article>

        <article class="panel coach-panel">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">AI coach</p>
              <h2>Plain-English money moves</h2>
            </div>
            <Sparkles size={22} />
          </div>

          <div class="insight-list">
            {#each dashboard.insights as insight}
              <section class={`insight ${insight.tone}`}>
                <strong>{insight.title}</strong>
                <p>{insight.body}</p>
              </section>
            {/each}
          </div>
        </article>

        <article class="panel recurring-panel">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Debit order detective</p>
              <h2>Recurring costs to review</h2>
            </div>
            <CircleDollarSign size={22} />
          </div>

          <div class="recurring-list">
            {#each dashboard.recurring as payment}
              <section class="recurring-item">
                <div>
                  <strong>{payment.merchant}</strong>
                  <span>{payment.category} / seen {payment.paymentsSeen} times</span>
                </div>
                <div>
                  <strong>{formatCurrency(payment.monthlyCost)}</strong>
                  <span>{formatCurrency(payment.yearlyCost)}/year</span>
                </div>
                <mark class={payment.status}>{payment.status}</mark>
              </section>
            {/each}
          </div>
        </article>

        <article class="panel trend-panel">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Cash flow</p>
              <h2>Income versus spending</h2>
            </div>
            <TrendingUp size={22} />
          </div>

          <div class="trend-chart">
            {#each dashboard.trends as trend}
              <div class="trend-month">
                <div class="trend-bars">
                  <span class="income-bar" style={`height: ${barHeight(trend.income)};`}></span>
                  <span class="spending-bar" style={`height: ${barHeight(trend.spending)};`}></span>
                </div>
                <span>{trend.month}</span>
              </div>
            {/each}
          </div>
          <div class="legend">
            <span><i class="income-dot"></i>Income</span>
            <span><i class="spending-dot"></i>Spending</span>
          </div>
        </article>

        <article class="panel goals-panel">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Goals</p>
              <h2>Realistic next moves</h2>
            </div>
            <Target size={22} />
          </div>

          <div class="goal-list">
            {#each dashboard.goals as goal}
              <section class="goal-item">
                <div class="goal-topline">
                  <div>
                    <strong>{goal.name}</strong>
                    <span>{goal.status}</span>
                  </div>
                  <strong>{formatCurrency(goal.saved)} / {formatCurrency(goal.target)}</strong>
                </div>
                <div class="goal-track" aria-hidden="true">
                  <span style={`width: ${goalProgress(goal)}%;`}></span>
                </div>
                <div class="goal-footer">
                  <span><CalendarDays size={15} /> {goal.deadline}</span>
                  <span><Wallet size={15} /> {formatCurrency(goal.recommendedMonthly)}/mo</span>
                </div>
              </section>
            {/each}
          </div>
        </article>
      </section>
    {:else if activeView === 'transactions'}
      <section class="table-panel">
        <div class="table-toolbar">
          <div>
            <p class="eyebrow">Transactions</p>
            <h2>{dashboard.meta.transactionCount} analysed transactions</h2>
          </div>
          <label class="search-box">
            <Search size={17} />
            <input bind:value={transactionSearch} placeholder="Search merchant or category" />
          </label>
        </div>

        <div class="transaction-table" role="table" aria-label="Categorised transactions">
          <div class="table-row table-head" role="row">
            <span>Date</span>
            <span>Merchant</span>
            <span>Category</span>
            <span>Amount</span>
          </div>
          {#each filteredTransactions as transaction}
            <div class="table-row" role="row">
              <span>{transaction.date}</span>
              <span>
                <strong>{transaction.merchant}</strong>
                <small>{transaction.description}</small>
              </span>
              <span>{transaction.category}</span>
              <span class={transaction.type}>{formatCurrency(transaction.amount)}</span>
            </div>
          {/each}
        </div>
      </section>
    {:else}
      <section class="privacy-grid">
        <article class="privacy-panel">
          <EyeOff size={24} />
          <div>
            <h2>Privacy-first statement analysis</h2>
            <p>
              The demo is designed around masked names, local sample data, and clear controls for
              deleting or exporting imported information.
            </p>
          </div>
        </article>
        <article class="privacy-control">
          <span>Mask merchant names</span>
          <strong>Enabled</strong>
        </article>
        <article class="privacy-control">
          <span>Delete imported data</span>
          <button>Clear demo</button>
        </article>
        <article class="privacy-control">
          <span>Export categorised CSV</span>
          <button>Prepare export</button>
        </article>
      </section>
    {/if}
  </section>
</main>
