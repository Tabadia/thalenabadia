import { tsParticles } from "https://cdn.jsdelivr.net/npm/@tsparticles/engine@3.0.3/+esm";
import { loadAll } from "https://cdn.jsdelivr.net/npm/@tsparticles/all@3.0.3/+esm";

(async () => {
	await loadAll(tsParticles);

	await tsParticles.addPreset("main", {
		fullScreen: {
			enable: true
		},
		particles: {
			links: {
				enable: true
			},
			move: {
				enable: true
			},
			number: {
				value: 50
			},
			opacity: {
				value: { min: 0.15, max: .75 }
			},
			shape: {
				type: ["circle", "square", "triangle", "polygon"],
				options: {
					polygon: [
						{
							sides: 5
						},
						{
							sides: 6
						},
						{
							sides: 8
						}
					]
				}
			},
			size: {
				value: { min: 1, max: 3 }
			}
        },
        "interactivity": {
            "detectsOn": "window",
            "events": {
                "onClick": {
                    "enable": true,
                    "mode": "push"
                },
                "onHover": {
                    "enable": true,
                    "mode": "repulse",
                },
                "resize": {
                    "delay": 0.5,
                    "enable": true
                }
            },
        }
	});

	await tsParticles.load({
		id: "particles",
		options: {
			preset: "main",
			particles: {
				color: {
					value: "#6600FF"
				},
				links: {
					color: "#6600FF"
				}
			}
		}
	});
})();
