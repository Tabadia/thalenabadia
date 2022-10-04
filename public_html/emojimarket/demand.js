var xValues = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30];
var yValues = Array.from({length: 30}, () => Math.floor(Math.random() * 12));

new Chart("myChart", {
  type: "line",
  data: {
    labels: xValues,
    datasets: [{
      fill: true,
      lineTension: 0,
      backgroundColor: "rgba(255,255,255,1.0)",
      borderColor: "rgba(255,255,255,0.5)",
      data: yValues
    },
]
  },

  options: {
    legend: {display: false},
    scales: {
        yAxes: [{
            scaleLabel: {
                display: true,
                labelString: 'Trades'}
            },],
        xAxes: [{
            scaleLabel: {
                display: true,
                labelString: 'Days',}
            },],}
        }})