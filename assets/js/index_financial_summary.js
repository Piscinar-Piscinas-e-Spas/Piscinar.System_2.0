(function () {
  const root = document.getElementById('financialSummaryApp');
  if (!root) {
    return;
  }

  const payload = JSON.parse(root.getAttribute('data-financial-payload') || '{}');
  const table = document.getElementById('financialHeatmapTable');
  const lowestContainer = document.getElementById('financialLowestMonths');
  const highestContainer = document.getElementById('financialHighestMonths');
  const currentMonthEl = document.getElementById('financialCurrentMonthTotal');
  const expectedEl = document.getElementById('financialExpectedValue');
  const indicatorBox = document.getElementById('financialIndicatorBox');
  const indicatorValue = document.getElementById('financialIndicatorValue');
  const sourceInputs = document.querySelectorAll('.financial-source-input');
  const easterTargets = [
    document.getElementById('financialSummarySecret'),
    document.getElementById('financialCashButton')
  ];
  let chart;

  const formatCurrency = (value) => new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(Number(value || 0));

  const isTypingTarget = (element) => {
    if (!element) {
      return false;
    }

    const tagName = (element.tagName || '').toLowerCase();
    return element.isContentEditable || ['input', 'textarea', 'select'].includes(tagName);
  };

  const toggleFinancialEasterEgg = () => {
    root.classList.toggle('is-easter-visible');
  };

  const getSelectedSources = () => {
    const selected = ['vendas'];
    sourceInputs.forEach((input) => {
      if (input.value !== 'vendas' && input.checked) {
        selected.push(input.value);
      }
    });
    return selected;
  };

  const buildAnalysis = (selectedSources) => {
    const yearsSet = new Set();
    const matrix = {};
    const heatValues = [];
    const rows = [];
    const yearTotals = {};
    const promotionCandidates = [];

    selectedSources.forEach((sourceKey) => {
      const sourceRows = payload.seriesBySource[sourceKey] || {};
      Object.entries(sourceRows).forEach(([yearKey, monthMap]) => {
        const year = Number(yearKey);
        if (!Number.isFinite(year) || year <= 0) {
          return;
        }

        yearsSet.add(year);
        matrix[year] = matrix[year] || {};

        Object.entries(monthMap || {}).forEach(([monthKey, amountValue]) => {
          const month = Number(monthKey);
          if (month < 1 || month > 12) {
            return;
          }

          matrix[year][month] = Number(matrix[year][month] || 0) + Number(amountValue || 0);
        });
      });
    });

    const years = Array.from(yearsSet).sort((a, b) => b - a);
    years.forEach((year) => {
      yearTotals[year] = 0;
    });

    for (let month = 1; month <= 12; month += 1) {
      const cells = [];
      const historicalAverageBase = [];

      years.forEach((year) => {
        const rawValue = Number((matrix[year] || {})[month] || 0);
        const isFutureCell = year === payload.currentYear && month > payload.currentMonth;
        const displayValue = isFutureCell ? null : rawValue;

        if (displayValue !== null) {
          yearTotals[year] += displayValue;
          heatValues.push(displayValue);
        }

        cells.push({ year, value: displayValue });

        if (displayValue === null) {
          return;
        }

        if (year === payload.currentYear && month === payload.currentMonth) {
          return;
        }

        historicalAverageBase.push(rawValue);
      });

      const monthAverage = historicalAverageBase.length
        ? historicalAverageBase.reduce((sum, value) => sum + value, 0) / historicalAverageBase.length
        : 0;

      rows.push({
        month,
        monthLabel: payload.monthLabels[month - 1],
        cells,
        monthAverage
      });

      if (monthAverage > 0) {
        promotionCandidates.push({
          month,
          label: payload.monthLabels[month - 1],
          average: monthAverage
        });
      }
    }

    promotionCandidates.sort((a, b) => a.average - b.average);

    const currentMonthAverage = Number((rows[payload.currentMonth - 1] || {}).monthAverage || 0);
    const expectedValue = payload.daysInMonth > 0
      ? (currentMonthAverage / payload.daysInMonth) * payload.currentDay
      : 0;

    return {
      years,
      rows,
      yearTotals,
      heatMin: heatValues.length ? Math.min(...heatValues) : 0,
      heatMax: heatValues.length ? Math.max(...heatValues) : 0,
      currentMonthTotal: Number((matrix[payload.currentYear] || {})[payload.currentMonth] || 0),
      expectedValue,
      chartValues: rows.map((row) => row.monthAverage),
      lowestMonths: promotionCandidates.slice(0, 3),
      highestMonths: promotionCandidates.slice().reverse().slice(0, 3)
    };
  };

  const heatColor = (value, min, max) => {
    if (value === null || value === undefined) {
      return '';
    }

    if (max <= min) {
      return 'rgba(68, 187, 68, 0.22)';
    }

    const ratio = Math.max(0, Math.min(1, (value - min) / (max - min)));
    const start = { r: 255, g: 68, b: 68 };
    const mid = { r: 255, g: 165, b: 0 };
    const end = { r: 68, g: 187, b: 68 };
    let color;

    if (ratio < 0.5) {
      const local = ratio / 0.5;
      color = {
        r: Math.round(start.r + ((mid.r - start.r) * local)),
        g: Math.round(start.g + ((mid.g - start.g) * local)),
        b: Math.round(start.b + ((mid.b - start.b) * local))
      };
    } else {
      const local = (ratio - 0.5) / 0.5;
      color = {
        r: Math.round(mid.r + ((end.r - mid.r) * local)),
        g: Math.round(mid.g + ((end.g - mid.g) * local)),
        b: Math.round(mid.b + ((end.b - mid.b) * local))
      };
    }

    return `rgba(${color.r}, ${color.g}, ${color.b}, 0.22)`;
  };

  const renderPromotions = (container, months, className) => {
    container.innerHTML = '';

    if (!months.length) {
      const empty = document.createElement('div');
      empty.className = 'text-muted small';
      empty.textContent = 'Sem historico suficiente.';
      container.appendChild(empty);
      return;
    }

    months.forEach((month) => {
      const item = document.createElement('div');
      item.className = `promotion-pill ${className}`;
      item.textContent = month.label;
      container.appendChild(item);
    });
  };

  const renderTable = (analysis) => {
    const thead = table.querySelector('thead tr');
    const tbody = table.querySelector('tbody');
    const tfoot = table.querySelector('tfoot tr');

    thead.innerHTML = '<th>Meses</th>';
    analysis.years.forEach((year) => {
      const th = document.createElement('th');
      th.textContent = year;
      thead.appendChild(th);
    });
    const averageTh = document.createElement('th');
    averageTh.textContent = 'Media Mes/Ano';
    thead.appendChild(averageTh);

    tbody.innerHTML = '';
    analysis.rows.forEach((row) => {
      const tr = document.createElement('tr');
      const monthTh = document.createElement('th');
      monthTh.textContent = row.monthLabel;
      tr.appendChild(monthTh);

      row.cells.forEach((cell) => {
        const td = document.createElement('td');
        if (cell.value === null) {
          td.innerHTML = '&nbsp;';
        } else {
          td.textContent = formatCurrency(cell.value);
          td.style.backgroundColor = heatColor(cell.value, analysis.heatMin, analysis.heatMax);
        }
        tr.appendChild(td);
      });

      const avgTd = document.createElement('td');
      avgTd.className = 'financial-average-cell';
      avgTd.textContent = formatCurrency(row.monthAverage);
      tr.appendChild(avgTd);
      tbody.appendChild(tr);
    });

    tfoot.innerHTML = '<th>Total Ano</th>';
    analysis.years.forEach((year) => {
      const td = document.createElement('td');
      td.textContent = formatCurrency(analysis.yearTotals[year] || 0);
      tfoot.appendChild(td);
    });
    const tail = document.createElement('td');
    tail.innerHTML = '&nbsp;';
    tfoot.appendChild(tail);
  };

  const renderChart = (analysis) => {
    const canvas = document.getElementById('financialAverageChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    if (chart) {
      chart.destroy();
    }

    chart = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: payload.monthShortLabels,
        datasets: [
          {
            type: 'bar',
            label: 'Media Mes/Ano',
            data: analysis.chartValues,
            backgroundColor: '#86a8d8',
            borderRadius: 8
          },
          {
            type: 'line',
            label: 'Tendencia',
            data: analysis.chartValues,
            borderColor: '#f4cf3a',
            backgroundColor: '#f4cf3a',
            borderWidth: 2,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y || 0)}`
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: (value) => formatCurrency(value)
            }
          }
        }
      }
    });
  };

  const renderAnalysis = () => {
    const analysis = buildAnalysis(getSelectedSources());
    const indicator = analysis.currentMonthTotal - analysis.expectedValue;

    renderTable(analysis);
    renderChart(analysis);
    renderPromotions(lowestContainer, analysis.lowestMonths, 'promotion-pill-low');
    renderPromotions(highestContainer, analysis.highestMonths, 'promotion-pill-high');

    currentMonthEl.textContent = formatCurrency(analysis.currentMonthTotal);
    expectedEl.textContent = formatCurrency(analysis.expectedValue);
    indicatorValue.textContent = formatCurrency(indicator);
    indicatorBox.classList.remove('financial-indicator-positive', 'financial-indicator-negative', 'financial-indicator-neutral');
    indicatorBox.classList.add(indicator > 0 ? 'financial-indicator-positive' : (indicator < 0 ? 'financial-indicator-negative' : 'financial-indicator-neutral'));
  };

  sourceInputs.forEach((input) => {
    input.addEventListener('change', renderAnalysis);
  });

  if (easterTargets.every(Boolean)) {
    document.addEventListener('keydown', (event) => {
      if (event.repeat || isTypingTarget(event.target)) {
        return;
      }

      const key = String(event.key || '').toLowerCase();
      if (!event.ctrlKey || !event.altKey || !event.shiftKey || key !== 'f') {
        return;
      }

      event.preventDefault();
      toggleFinancialEasterEgg();
    });
  }

  renderAnalysis();
})();
