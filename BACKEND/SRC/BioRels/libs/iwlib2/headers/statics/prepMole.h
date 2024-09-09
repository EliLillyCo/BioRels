#ifndef PREPMOLE_H
#define PREPMOLE_H
#include "headers/molecule/macromole.h"
namespace protspace
{
void processInput(const std::string& input,
                  const std::string& name,
                  MacroMole& mole);
void processInput(const std::string& input,
                  const std::string& name,
                  protspace::GroupList<protspace::MacroMole> & list);
}

#endif //PREPMOLE_H
