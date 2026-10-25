#!/usr/bin/env python3
"""Cuantiza el ONNX a int8 para reducir tamaño y latencia en CPU."""

from __future__ import annotations

import argparse
from pathlib import Path

import onnx
from onnxruntime.quantization import QuantType, quantize_dynamic


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", default="model/model.onnx")
    parser.add_argument("--output", default="model/model-int8.onnx")
    args = parser.parse_args()
    source = Path(args.input)
    target = Path(args.output)
    target.parent.mkdir(parents=True, exist_ok=True)

    # El modelo exportado por SetFit puede repetir dominios de opset; ONNX Runtime
    # necesita una lista limpia para cuantizar.
    model = onnx.load(str(source))
    versions: dict[str, int] = {}
    for item in model.opset_import:
        versions[item.domain] = max(versions.get(item.domain, 0), item.version)
    del model.opset_import[:]
    for domain, version in versions.items():
        item = model.opset_import.add()
        item.domain = domain
        item.version = version
    cleaned = target.with_name(target.stem + "-clean.onnx")
    onnx.save(model, str(cleaned))

    quantize_dynamic(str(cleaned), str(target), weight_type=QuantType.QInt8)
    cleaned.unlink(missing_ok=True)
    print(f"{target} · {target.stat().st_size / 1024 / 1024:.1f} MB")


if __name__ == "__main__":
    main()
