// src/components/plans/PlanCard.jsx
import React from 'react';
import { Check } from 'lucide-react';

const PlanCard = ({ plan, isPopular, isSelected, onSelect }) => {
  return (
    <div 
      onClick={() => onSelect(plan)}
      className={`relative rounded-3xl p-8 transition-all cursor-pointer ${
        isSelected 
          ? 'bg-gradient-to-br from-red-600 to-red-900 scale-105 shadow-2xl shadow-red-600/50' 
          : 'bg-gradient-to-br from-white/[0.05] to-white/[0.02] hover:from-white/[0.08] hover:to-white/[0.04]'
      } border ${isSelected ? 'border-red-500' : 'border-white/10'}`}
    >
      {isPopular && (
        <div className="absolute -top-4 left-1/2 -translate-x-1/2">
          <div className="bg-red-600 text-white text-xs font-black uppercase px-6 py-2 rounded-full shadow-lg">
            MAIS POPULAR
          </div>
        </div>
      )}

      {isSelected && (
        <div className="absolute -top-3 -right-3">
          <div className="bg-white text-red-600 rounded-full p-2 shadow-lg">
            <Check className="w-5 h-5" />
          </div>
        </div>
      )}

      <div className="text-center mb-6">
        <div className="inline-flex items-center gap-2 mb-4">
          {plan.icon}
          <h3 className="text-2xl font-black uppercase">{plan.name}</h3>
        </div>
        <p className={`text-sm ${isSelected ? 'text-red-100' : 'text-zinc-400'}`}>
          {plan.description}
        </p>
      </div>

      <div className="text-center mb-8">
        <div className="flex items-baseline justify-center gap-2">
          <span className="text-sm opacity-70">R$</span>
          <span className="text-6xl font-black">{plan.price}</span>
          <span className="text-sm opacity-70">/mês</span>
        </div>
      </div>

      <div className="space-y-3 mb-8">
        {plan.features.map((feature, idx) => (
          <div key={idx} className="flex items-start gap-3">
            <div className={`flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center mt-0.5 ${
              isSelected ? 'bg-white/20' : 'bg-red-600/20'
            }`}>
              <Check className={`w-3 h-3 ${isSelected ? 'text-white' : 'text-red-600'}`} />
            </div>
            <span className={`text-sm ${isSelected ? 'text-white' : 'text-zinc-300'}`}>
              {feature}
            </span>
          </div>
        ))}
      </div>

      {plan.highlight && (
        <div className={`p-4 rounded-xl mb-6 ${
          isSelected 
            ? 'bg-white/10 border border-white/20' 
            : 'bg-red-600/10 border border-red-600/20'
        }`}>
          <p className="text-xs font-semibold text-center">
            {plan.highlight}
          </p>
        </div>
      )}

      <button className={`w-full py-4 rounded-xl font-black uppercase tracking-wider transition-all ${
        isSelected
          ? 'bg-white text-red-600 hover:bg-red-50'
          : 'bg-red-600 text-white hover:bg-red-700'
      }`}>
        {isSelected ? 'Selecionado' : 'Selecionar Plano'}
      </button>
    </div>
  );
};

export default PlanCard;