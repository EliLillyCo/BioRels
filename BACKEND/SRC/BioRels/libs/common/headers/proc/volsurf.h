#ifndef VOLSURF_H
#define VOLSURF_H
#include <cstdint>
#include <vector>
#include <string>
namespace protspace
{
class Coords;
class MacroMole;
typedef uint16_t metric;
    double getOverlap(MacroMole& pMole1,
                      MacroMole& pMole2,
                      const metric& pMetric);
    void getFiboSphere( std::vector<Coords>& list,const int& sample=500);
    void genSurface(protspace::MacroMole& mole, protspace::MacroMole& surf,
                    const size_t& precision=500);
    double getVolume(MacroMole& mole,const double& step=0.15,const std::string& pFile="");
}

#endif // VOLSURF_H

